<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function adminDeblocage(string $identifiant = 'admin-deblocage'): User
{
    return User::firstOrCreate(
        ['identifiant' => $identifiant],
        [
            'name' => 'Admin '.$identifiant,
            'password' => 'MotDePasse-2026',
            'role' => Role::Administrateur,
            'actif' => true,
        ],
    );
}

function secretaireDeblocage(string $identifiant = 'secretaire-deblocage'): User
{
    return User::firstOrCreate(
        ['identifiant' => $identifiant],
        [
            'name' => 'Secrétaire '.$identifiant,
            'password' => 'MotDePasse-2026',
            'role' => Role::Secretariat,
            'actif' => true,
        ],
    );
}

function compteBloque(string $identifiant = 'bloque-deblocage'): User
{
    $user = secretaireDeblocage($identifiant);
    $user->forceFill(['failed_login_attempts' => 5, 'locked_until' => now()->addMinutes(15)])->save();

    return $user->fresh();
}

// R13 : déblocage par l'administrateur

it('renvoie un visiteur vers la connexion et laisse le compte bloqué', function () {
    $bloque = compteBloque();

    $this->post(route('comptes.debloquer', $bloque))->assertRedirect(route('connexion'));

    expect($bloque->fresh()->isLocked())->toBeTrue();
});

it('refuse le déblocage au secrétariat', function () {
    $bloque = compteBloque();

    $this->actingAs(secretaireDeblocage('autre-secretaire'))
        ->post(route('comptes.debloquer', $bloque))
        ->assertForbidden();

    expect($bloque->fresh()->isLocked())->toBeTrue();
});

it('permet à l\'administrateur de débloquer un compte', function () {
    $bloque = compteBloque();

    expect($bloque->isLocked())->toBeTrue();

    $this->actingAs(adminDeblocage())
        ->post(route('comptes.debloquer', $bloque))
        ->assertRedirect(route('comptes.index'))
        ->assertSessionHas('statut');

    $bloque->refresh();

    expect($bloque->isLocked())->toBeFalse()
        ->and($bloque->failed_login_attempts)->toBe(0)
        ->and($bloque->locked_until)->toBeNull();
});

it('permet au compte débloqué de se connecter à nouveau', function () {
    $bloque = compteBloque();
    $identifiants = ['identifiant' => $bloque->identifiant, 'password' => 'MotDePasse-2026'];

    $this->post(route('connexion.soumettre'), $identifiants)->assertSessionHasErrors('identifiant');
    $this->assertGuest();

    $this->actingAs(adminDeblocage())->post(route('comptes.debloquer', $bloque));
    $this->post(route('deconnexion'));

    $this->post(route('connexion.soumettre'), $identifiants)->assertRedirect(route('accueil'));
    $this->assertAuthenticatedAs($bloque->fresh());
});

it('enregistre le déblocage dans le journal avec son auteur', function () {
    $admin = adminDeblocage();
    $bloque = compteBloque();

    $this->actingAs($admin)->post(route('comptes.debloquer', $bloque));

    $nombre = DB::table('activity_log')
        ->where('log_name', 'comptes')
        ->where('event', 'unlocked')
        ->where('causer_id', $admin->id)
        ->where('subject_id', $bloque->id)
        ->count();

    expect($nombre)->toBe(1);
});

it('prévient quand le compte n\'est pas bloqué, sans rien journaliser', function () {
    $libre = secretaireDeblocage('libre-deblocage');

    $this->actingAs(adminDeblocage())
        ->post(route('comptes.debloquer', $libre))
        ->assertRedirect(route('comptes.index'))
        ->assertSessionHas('statut', fn ($message) => str_contains($message, 'pas bloqué'));

    expect(DB::table('activity_log')->where('event', 'unlocked')->count())->toBe(0);
});

it('refuse le déblocage par un simple lien (GET)', function () {
    $bloque = compteBloque();

    $this->actingAs(adminDeblocage())
        ->get('/comptes/'.$bloque->id.'/debloquer')
        ->assertStatus(405);
});

it('répond 404 pour un compte inexistant', function () {
    $this->actingAs(adminDeblocage())
        ->post('/comptes/99999/debloquer')
        ->assertNotFound();
});

it('affiche l\'état bloqué et le bouton seulement pour un compte bloqué', function () {
    $admin = adminDeblocage();
    secretaireDeblocage('libre-liste');

    $this->actingAs($admin)->get(route('comptes.index'))
        ->assertDontSee('Bloqué')
        ->assertDontSee('Débloquer');

    $bloque = compteBloque('bloque-liste');

    $this->actingAs($admin)->get(route('comptes.index'))
        ->assertSee('Bloqué')
        ->assertSee('Débloquer')
        ->assertSee(route('comptes.debloquer', $bloque));
});

// R14 : il reste toujours un administrateur actif

it('refuse de désactiver le dernier administrateur actif', function () {
    $x = adminDeblocage('admin-x');
    $y = adminDeblocage('admin-y');

    $this->actingAs($y);
    // Simule la désactivation simultanée de l'auteur par un autre administrateur.
    User::whereKey($y->id)->update(['actif' => false]);

    $this->put(route('comptes.update', $x), [
        'name' => $x->name,
        'identifiant' => $x->identifiant,
        'role' => 'administrateur',
    ])->assertSessionHasErrors('actif');

    expect($x->fresh()->actif)->toBeTrue();
});

it('refuse de retirer le rôle du dernier administrateur actif', function () {
    $x = adminDeblocage('admin-x');
    $y = adminDeblocage('admin-y');

    $this->actingAs($y);
    User::whereKey($y->id)->update(['actif' => false]);

    $this->put(route('comptes.update', $x), [
        'name' => $x->name,
        'identifiant' => $x->identifiant,
        'role' => 'secretariat',
        'actif' => '1',
    ])->assertSessionHasErrors('role');

    expect($x->fresh()->role)->toBe(Role::Administrateur);
});

it('permet à un administrateur de retirer le rôle d\'un autre quand un administrateur actif reste', function () {
    $x = adminDeblocage('admin-x');
    $y = adminDeblocage('admin-y');

    $this->actingAs($y)->put(route('comptes.update', $x), [
        'name' => $x->name,
        'identifiant' => $x->identifiant,
        'role' => 'secretariat',
        'actif' => '1',
    ])->assertSessionHasNoErrors()->assertRedirect(route('comptes.index'));

    expect($x->fresh()->role)->toBe(Role::Secretariat);
});

it('permet de désactiver un compte secrétariat même avec un seul administrateur', function () {
    $admin = adminDeblocage();
    $secretaire = secretaireDeblocage();

    $this->actingAs($admin)->put(route('comptes.update', $secretaire), [
        'name' => $secretaire->name,
        'identifiant' => $secretaire->identifiant,
        'role' => 'secretariat',
    ])->assertSessionHasNoErrors();

    expect($secretaire->fresh()->actif)->toBeFalse();
});
