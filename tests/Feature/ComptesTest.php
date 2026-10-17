<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function unAdministrateur(array $attributs = []): User
{
    return User::create(array_merge([
        'name' => 'Administrateur Test',
        'identifiant' => 'admin-test',
        'password' => 'MotDePasse-2026',
        'role' => Role::Administrateur,
        'actif' => true,
    ], $attributs));
}

function unSecretaire(array $attributs = []): User
{
    return User::create(array_merge([
        'name' => 'Awa Secrétaire',
        'identifiant' => 'secretaire-test',
        'password' => 'MotDePasse-2026',
        'role' => Role::Secretariat,
        'actif' => true,
    ], $attributs));
}

function donneesCompte(array $surcharge = []): array
{
    return array_merge([
        'name' => 'Koffi Adjovi',
        'identifiant' => 'koffi',
        'password' => 'Nouveau-2026-Pw',
        'password_confirmation' => 'Nouveau-2026-Pw',
        'role' => 'secretariat',
        'actif' => '1',
    ], $surcharge);
}

// Accès et droits

it('renvoie un visiteur non connecté vers la page de connexion', function () {
    $this->get(route('comptes.index'))->assertRedirect(route('connexion'));
});

it('interdit toutes les pages de gestion des comptes au secrétariat', function () {
    $secretaire = unSecretaire();
    $cible = unAdministrateur();

    $this->actingAs($secretaire);

    $this->get(route('comptes.index'))->assertForbidden();
    $this->get(route('comptes.create'))->assertForbidden();
    $this->post(route('comptes.store'), donneesCompte())->assertForbidden();
    $this->get(route('comptes.edit', $cible))->assertForbidden();
    $this->put(route('comptes.update', $cible), donneesCompte())->assertForbidden();

    expect(User::where('identifiant', 'koffi')->exists())->toBeFalse();
});

it('affiche le lien de gestion des comptes seulement à l\'administrateur', function () {
    $this->actingAs(unAdministrateur())->get(route('accueil'))->assertSee('Gérer les comptes');

    $this->post(route('deconnexion'));

    $this->actingAs(unSecretaire())->get(route('accueil'))->assertDontSee('Gérer les comptes');
});

// Liste

it('affiche la liste des comptes avec un tableau structuré', function () {
    $admin = unAdministrateur();
    unSecretaire();

    $this->actingAs($admin)->get(route('comptes.index'))
        ->assertOk()
        ->assertSee('Awa Secrétaire')
        ->assertSee('secretaire-test')
        ->assertSee('Secrétariat')
        ->assertSee('<caption', false)
        ->assertSee('scope="col"', false);
});

// Création

it('affiche le formulaire de création avec des champs étiquetés', function () {
    $this->actingAs(unAdministrateur())->get(route('comptes.create'))
        ->assertOk()
        ->assertSee('Nom complet')
        ->assertSee('Identifiant')
        ->assertSee('Mot de passe')
        ->assertSee('Confirmation du mot de passe')
        ->assertSee('Rôle');
});

it('crée un compte avec un mot de passe haché', function () {
    $this->actingAs(unAdministrateur())
        ->post(route('comptes.store'), donneesCompte())
        ->assertRedirect(route('comptes.index'))
        ->assertSessionHas('statut');

    $compte = User::where('identifiant', 'koffi')->firstOrFail();

    expect($compte->role)->toBe(Role::Secretariat)
        ->and($compte->actif)->toBeTrue()
        ->and($compte->password)->not->toBe('Nouveau-2026-Pw')
        ->and(Hash::check('Nouveau-2026-Pw', $compte->password))->toBeTrue();
});

it('signale les champs obligatoires manquants', function () {
    $this->actingAs(unAdministrateur())
        ->post(route('comptes.store'), [])
        ->assertSessionHasErrors(['name', 'identifiant', 'password', 'role']);
});

it('refuse un mot de passe de moins de 8 caractères', function () {
    $this->actingAs(unAdministrateur())
        ->post(route('comptes.store'), donneesCompte([
            'password' => 'admin26',
            'password_confirmation' => 'admin26',
        ]))
        ->assertSessionHasErrors('password');

    expect(User::where('identifiant', 'koffi')->exists())->toBeFalse();
});

it('refuse une confirmation de mot de passe différente', function () {
    $this->actingAs(unAdministrateur())
        ->post(route('comptes.store'), donneesCompte(['password_confirmation' => 'Autre-2026-Pw']))
        ->assertSessionHasErrors('password');
});

it('refuse un identifiant déjà utilisé', function () {
    unSecretaire(['identifiant' => 'koffi']);

    $this->actingAs(unAdministrateur())
        ->post(route('comptes.store'), donneesCompte())
        ->assertSessionHasErrors('identifiant');
});

it('refuse un rôle qui n\'existe pas', function () {
    $this->actingAs(unAdministrateur())
        ->post(route('comptes.store'), donneesCompte(['role' => 'superutilisateur']))
        ->assertSessionHasErrors('role');
});

// Modification, désactivation, réactivation

it('affiche le formulaire de modification avec les valeurs du compte', function () {
    $secretaire = unSecretaire();

    $this->actingAs(unAdministrateur())->get(route('comptes.edit', $secretaire))
        ->assertOk()
        ->assertSee('Awa Secrétaire')
        ->assertSee('secretaire-test');
});

it('modifie le nom et le rôle d\'un compte', function () {
    $secretaire = unSecretaire();

    $this->actingAs(unAdministrateur())
        ->put(route('comptes.update', $secretaire), [
            'name' => 'Awa Directrice',
            'identifiant' => 'secretaire-test',
            'role' => 'administrateur',
            'actif' => '1',
        ])
        ->assertRedirect(route('comptes.index'));

    $secretaire->refresh();

    expect($secretaire->name)->toBe('Awa Directrice')
        ->and($secretaire->role)->toBe(Role::Administrateur);
});

it('conserve l\'ancien mot de passe quand on n\'en saisit pas de nouveau', function () {
    $secretaire = unSecretaire();
    $ancien = $secretaire->password;

    $this->actingAs(unAdministrateur())->put(route('comptes.update', $secretaire), [
        'name' => 'Awa Modifiée',
        'identifiant' => 'secretaire-test',
        'role' => 'secretariat',
        'actif' => '1',
    ]);

    expect($secretaire->fresh()->password)->toBe($ancien);
});

it('change le mot de passe quand on en saisit un nouveau', function () {
    $secretaire = unSecretaire();

    $this->actingAs(unAdministrateur())->put(route('comptes.update', $secretaire), [
        'name' => 'Awa Secrétaire',
        'identifiant' => 'secretaire-test',
        'role' => 'secretariat',
        'actif' => '1',
        'password' => 'Autre-2026-Pw',
        'password_confirmation' => 'Autre-2026-Pw',
    ])->assertRedirect(route('comptes.index'));

    expect(Hash::check('Autre-2026-Pw', $secretaire->fresh()->password))->toBeTrue();
});

it('accepte de garder son propre identifiant lors d\'une modification', function () {
    $secretaire = unSecretaire();

    $this->actingAs(unAdministrateur())->put(route('comptes.update', $secretaire), [
        'name' => 'Awa Secrétaire',
        'identifiant' => 'secretaire-test',
        'role' => 'secretariat',
        'actif' => '1',
    ])->assertSessionHasNoErrors();
});

it('refuse de prendre l\'identifiant d\'un autre compte', function () {
    $secretaire = unSecretaire();
    unSecretaire(['identifiant' => 'deja-pris']);

    $this->actingAs(unAdministrateur())->put(route('comptes.update', $secretaire), [
        'name' => 'Awa Secrétaire',
        'identifiant' => 'deja-pris',
        'role' => 'secretariat',
        'actif' => '1',
    ])->assertSessionHasErrors('identifiant');
});

it('désactive puis réactive un compte', function () {
    $secretaire = unSecretaire();
    $admin = unAdministrateur();

    $this->actingAs($admin)->put(route('comptes.update', $secretaire), [
        'name' => 'Awa Secrétaire',
        'identifiant' => 'secretaire-test',
        'role' => 'secretariat',
    ]);

    expect($secretaire->fresh()->actif)->toBeFalse();

    $this->actingAs($admin)->put(route('comptes.update', $secretaire), [
        'name' => 'Awa Secrétaire',
        'identifiant' => 'secretaire-test',
        'role' => 'secretariat',
        'actif' => '1',
    ]);

    expect($secretaire->fresh()->actif)->toBeTrue();
});

it('déconnecte un utilisateur dont le compte vient d\'être désactivé', function () {
    $secretaire = unSecretaire();
    $this->actingAs($secretaire);

    $secretaire->forceFill(['actif' => false])->save();

    $this->get(route('accueil'))->assertRedirect(route('connexion'));
    $this->assertGuest();
});

it('empêche un administrateur de se désactiver lui-même', function () {
    $admin = unAdministrateur();

    $this->actingAs($admin)->put(route('comptes.update', $admin), [
        'name' => $admin->name,
        'identifiant' => $admin->identifiant,
        'role' => 'administrateur',
    ])->assertSessionHasErrors('actif');

    expect($admin->fresh()->actif)->toBeTrue();
});

it('empêche un administrateur de retirer son propre rôle', function () {
    $admin = unAdministrateur();

    $this->actingAs($admin)->put(route('comptes.update', $admin), [
        'name' => $admin->name,
        'identifiant' => $admin->identifiant,
        'role' => 'secretariat',
        'actif' => '1',
    ])->assertSessionHasErrors('role');

    expect($admin->fresh()->role)->toBe(Role::Administrateur);
});

// Journal d'audit

it('enregistre la création d\'un compte dans le journal avec son auteur', function () {
    $admin = unAdministrateur();

    $this->actingAs($admin)->post(route('comptes.store'), donneesCompte());

    $nombre = DB::table('activity_log')
        ->where('log_name', 'comptes')
        ->where('event', 'created')
        ->where('causer_id', $admin->id)
        ->count();

    expect($nombre)->toBe(1);
});

it('n\'écrit jamais de mot de passe dans le journal', function () {
    $admin = unAdministrateur();

    $this->actingAs($admin)->post(route('comptes.store'), donneesCompte([
        'password' => 'ValeurSecrete-2026',
        'password_confirmation' => 'ValeurSecrete-2026',
    ]));

    $contenu = json_encode(DB::table('activity_log')->get());

    expect($contenu)->not->toContain('ValeurSecrete-2026')
        ->and($contenu)->not->toContain('$2y$');
});
