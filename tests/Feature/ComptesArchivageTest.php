<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function adminArchive(string $identifiant = 'admin-archive'): User
{
    return User::firstOrCreate(
        ['identifiant' => $identifiant],
        ['name' => 'Admin '.$identifiant, 'password' => 'MotDePasse-2026', 'role' => Role::Administrateur, 'actif' => true],
    );
}

function secretaireArchive(string $identifiant = 'secretaire-archive'): User
{
    return User::firstOrCreate(
        ['identifiant' => $identifiant],
        ['name' => 'Secrétaire '.$identifiant, 'password' => 'MotDePasse-2026', 'role' => Role::Secretariat, 'actif' => true],
    );
}

function journalArchive(string $evenement, int $auteur, int $cible): int
{
    return DB::table('activity_log')
        ->where('log_name', 'comptes')
        ->where('event', $evenement)
        ->where('causer_id', $auteur)
        ->where('subject_id', $cible)
        ->count();
}

// Accès

it('renvoie un visiteur vers la connexion et ne change rien', function () {
    $compte = secretaireArchive();

    $this->get(route('comptes.archiver.confirmer', $compte))->assertRedirect(route('connexion'));
    $this->post(route('comptes.archiver', $compte))->assertRedirect(route('connexion'));
    $this->get(route('comptes.archives'))->assertRedirect(route('connexion'));
    $this->post(route('comptes.restaurer', $compte))->assertRedirect(route('connexion'));

    expect($compte->fresh()->trashed())->toBeFalse();
});

it('interdit l\'archivage et la restauration au secrétariat', function () {
    $acteur = secretaireArchive('acteur-secretariat');
    $cible = secretaireArchive('cible-secretariat');

    $this->actingAs($acteur);

    $this->get(route('comptes.archiver.confirmer', $cible))->assertForbidden();
    $this->post(route('comptes.archiver', $cible))->assertForbidden();
    $this->get(route('comptes.archives'))->assertForbidden();
    $this->post(route('comptes.restaurer', $cible))->assertForbidden();

    expect($cible->fresh()->trashed())->toBeFalse();
});

it('répond 404 pour un compte qui n\'existe pas', function () {
    $this->actingAs(adminArchive())
        ->post('/comptes/99999/archiver')
        ->assertNotFound();
});

// R23 : confirmation puis archivage

it('affiche une page de confirmation sans archiver', function () {
    $compte = secretaireArchive();

    $this->actingAs(adminArchive())
        ->get(route('comptes.archiver.confirmer', $compte))
        ->assertOk()
        ->assertSee('Archiver le compte')
        ->assertSee($compte->name)
        ->assertSee('Annuler')
        ->assertSee(route('comptes.archiver', $compte));

    expect($compte->fresh()->trashed())->toBeFalse();
});

it('archive un compte après confirmation', function () {
    $compte = secretaireArchive();

    $this->actingAs(adminArchive())
        ->post(route('comptes.archiver', $compte))
        ->assertRedirect(route('comptes.index'))
        ->assertSessionHas('statut');

    expect(User::find($compte->id))->toBeNull()
        ->and(User::withTrashed()->whereKey($compte->id)->exists())->toBeTrue();
});

// R25 : effets de l'archivage

it('retire le compte archivé de la liste des comptes', function () {
    $admin = adminArchive();
    $compte = secretaireArchive();

    $this->actingAs($admin)->post(route('comptes.archiver', $compte));

    $this->get(route('comptes.index'))->assertDontSee(route('comptes.edit', $compte));
});

it('empêche un compte archivé de se connecter, avec le message neutre', function () {
    $compte = secretaireArchive();
    $compte->delete();

    $this->post(route('connexion.soumettre'), ['identifiant' => $compte->identifiant, 'password' => 'MotDePasse-2026'])
        ->assertSessionHasErrors(['identifiant' => 'Identifiant ou mot de passe incorrect.']);

    $this->assertGuest();
});

it('garde l\'identifiant et l\'e-mail d\'un compte archivé réservés', function () {
    $compte = secretaireArchive('identifiant-reserve');
    $compte->delete();

    $this->actingAs(adminArchive())
        ->post(route('comptes.store'), [
            'name' => 'Autre Personne',
            'identifiant' => 'identifiant-reserve',
            'password' => 'Nouveau-2026-Pw',
            'password_confirmation' => 'Nouveau-2026-Pw',
            'role' => 'secretariat',
            'actif' => '1',
        ])
        ->assertSessionHasErrors('identifiant');
});

// R24 : garde-fous

it('refuse d\'archiver son propre compte', function () {
    $admin = adminArchive();

    $this->actingAs($admin)
        ->post(route('comptes.archiver', $admin))
        ->assertRedirect(route('comptes.index'))
        ->assertSessionHasErrors('archive');

    expect($admin->fresh()->trashed())->toBeFalse();
});

it('refuse d\'archiver le dernier administrateur actif', function () {
    $x = adminArchive('admin-x');
    $y = adminArchive('admin-y');

    $this->actingAs($y);
    // Simule la désactivation simultanée de l'auteur par un autre administrateur.
    User::whereKey($y->id)->update(['actif' => false]);

    $this->post(route('comptes.archiver', $x))->assertSessionHasErrors('archive');

    expect($x->fresh()->trashed())->toBeFalse();
});

it('permet d\'archiver un administrateur quand un autre administrateur actif reste', function () {
    $x = adminArchive('admin-x');
    $y = adminArchive('admin-y');

    $this->actingAs($y)
        ->post(route('comptes.archiver', $x))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('comptes.index'));

    expect(User::find($x->id))->toBeNull();
});

it('ne propose pas d\'archiver son propre compte dans la liste', function () {
    $admin = adminArchive();
    $autre = secretaireArchive();

    $this->actingAs($admin)->get(route('comptes.index'))
        ->assertSee(route('comptes.archiver.confirmer', $autre))
        ->assertDontSee(route('comptes.archiver.confirmer', $admin));
});

// R26 : archives et restauration

it('liste seulement les comptes archivés dans les archives', function () {
    $actif = secretaireArchive('compte-actif');
    $archive = secretaireArchive('compte-archive');
    $archive->delete();

    $this->actingAs(adminArchive())->get(route('comptes.archives'))
        ->assertOk()
        ->assertSee('compte-archive')
        ->assertDontSee('compte-actif')
        ->assertSee(route('comptes.restaurer', $archive));
});

it('restaure un compte archivé', function () {
    $compte = secretaireArchive();
    $compte->delete();

    $this->actingAs(adminArchive())
        ->post(route('comptes.restaurer', $compte))
        ->assertRedirect(route('comptes.archives'))
        ->assertSessionHas('statut');

    expect(User::find($compte->id))->not->toBeNull();
});

it('permet à un compte restauré de se connecter avec son mot de passe', function () {
    $compte = secretaireArchive();
    $compte->delete();

    $this->actingAs(adminArchive())->post(route('comptes.restaurer', $compte));
    $this->post(route('deconnexion'));

    $this->post(route('connexion.soumettre'), ['identifiant' => $compte->identifiant, 'password' => 'MotDePasse-2026'])
        ->assertRedirect(route('accueil'));

    $this->assertAuthenticated();
});

it('prévient quand on restaure un compte qui n\'est pas archivé', function () {
    $compte = secretaireArchive();
    $admin = adminArchive();

    $this->actingAs($admin)
        ->post(route('comptes.restaurer', $compte))
        ->assertSessionHas('statut', fn ($message) => str_contains($message, 'pas archivé'));

    expect(journalArchive('unarchived', $admin->id, $compte->id))->toBe(0);
});

// R30 : journal

it('journalise l\'archivage et la restauration avec leur auteur', function () {
    $admin = adminArchive();
    $compte = secretaireArchive();

    $this->actingAs($admin)->post(route('comptes.archiver', $compte));
    expect(journalArchive('archived', $admin->id, $compte->id))->toBe(1);

    $this->post(route('comptes.restaurer', $compte));
    expect(journalArchive('unarchived', $admin->id, $compte->id))->toBe(1);
});
