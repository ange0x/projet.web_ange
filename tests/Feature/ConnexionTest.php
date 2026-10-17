<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function creerCompte(array $attributs = []): User
{
    return User::create(array_merge([
        'name' => 'Awa Secrétaire',
        'identifiant' => 'awa',
        'password' => 'MotDePasse-2026',
        'role' => Role::Secretariat,
        'actif' => true,
    ], $attributs));
}

function essayerConnexion($test, string $motDePasse)
{
    return $test->post(route('connexion.soumettre'), [
        'identifiant' => 'awa',
        'password' => $motDePasse,
    ]);
}

// US1 : se connecter

it('affiche la page de connexion avec des champs étiquetés', function () {
    $this->get(route('connexion'))
        ->assertOk()
        ->assertSee('Identifiant')
        ->assertSee('Mot de passe')
        ->assertSee('Se connecter');
});

it('connecte un utilisateur avec des identifiants corrects', function () {
    $user = creerCompte();

    essayerConnexion($this, 'MotDePasse-2026')->assertRedirect(route('accueil'));

    $this->assertAuthenticatedAs($user);
});

it('refuse un mot de passe faux avec un message neutre', function () {
    creerCompte();

    essayerConnexion($this, 'faux')
        ->assertSessionHasErrors(['identifiant' => 'Identifiant ou mot de passe incorrect.']);

    $this->assertGuest();
});

it('donne le même message pour un identifiant inconnu', function () {
    $this->post(route('connexion.soumettre'), [
        'identifiant' => 'inconnu',
        'password' => 'MotDePasse-2026',
    ])->assertSessionHasErrors(['identifiant' => 'Identifiant ou mot de passe incorrect.']);

    $this->assertGuest();
});

it('refuse un compte désactivé avec le même message', function () {
    creerCompte(['actif' => false]);

    essayerConnexion($this, 'MotDePasse-2026')
        ->assertSessionHasErrors(['identifiant' => 'Identifiant ou mot de passe incorrect.']);

    $this->assertGuest();
});

it('signale les champs vides', function () {
    $this->post(route('connexion.soumettre'), [])
        ->assertSessionHasErrors(['identifiant', 'password']);
});

it('bloque le compte après 5 échecs, même avec le bon mot de passe', function () {
    creerCompte();

    foreach (range(1, 5) as $i) {
        essayerConnexion($this, 'faux');
    }

    essayerConnexion($this, 'MotDePasse-2026')->assertSessionHasErrors('identifiant');
    $this->assertGuest();
});

it('débloque le compte après 15 minutes', function () {
    creerCompte();

    foreach (range(1, 5) as $i) {
        essayerConnexion($this, 'faux');
    }

    $this->travel(16)->minutes();

    essayerConnexion($this, 'MotDePasse-2026')->assertRedirect(route('accueil'));
    $this->assertAuthenticated();
});

it('ne reverrouille pas le compte au premier échec après le blocage', function () {
    $user = creerCompte();

    foreach (range(1, 5) as $i) {
        essayerConnexion($this, 'faux');
    }

    $this->travel(16)->minutes();
    essayerConnexion($this, 'faux');

    expect($user->fresh()->isLocked())->toBeFalse();
});

it('enregistre la connexion dans le journal', function () {
    creerCompte();

    essayerConnexion($this, 'MotDePasse-2026');

    expect(DB::table('activity_log')->where('log_name', 'auth')->where('event', 'login')->count())->toBe(1);
});

it('renvoie un visiteur non connecté vers la page de connexion', function () {
    $this->get(route('accueil'))->assertRedirect(route('connexion'));
});

// US2 : se déconnecter

it('déconnecte l\'utilisateur et le renvoie à la page de connexion', function () {
    $user = creerCompte();

    $this->actingAs($user)->post(route('deconnexion'))->assertRedirect(route('connexion'));

    $this->assertGuest();
});

it('interdit la déconnexion par un simple lien (GET)', function () {
    $user = creerCompte();

    $this->actingAs($user)->get('/deconnexion')->assertStatus(405);
});
