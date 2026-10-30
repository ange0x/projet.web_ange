<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adminEmail(): User
{
    return User::firstOrCreate(
        ['identifiant' => 'admin-email'],
        ['name' => 'Admin Email', 'password' => 'MotDePasse-2026', 'role' => Role::Administrateur, 'actif' => true],
    );
}

function compteEmail(string $identifiant, ?string $email = null): User
{
    return User::firstOrCreate(
        ['identifiant' => $identifiant],
        ['name' => 'Compte '.$identifiant, 'password' => 'MotDePasse-2026', 'role' => Role::Secretariat, 'actif' => true, 'email' => $email],
    );
}

function donneesEmail(array $surcharge = []): array
{
    return array_merge([
        'name' => 'Koffi Adjovi',
        'identifiant' => 'koffi',
        'password' => 'Nouveau-2026-Pw',
        'password_confirmation' => 'Nouveau-2026-Pw',
        'role' => 'secretariat',
        'actif' => '1',
        'email' => 'koffi@exemple.com',
    ], $surcharge);
}

function modifEmail(User $cible, array $surcharge = []): array
{
    return array_merge([
        'name' => $cible->name,
        'identifiant' => $cible->identifiant,
        'role' => 'secretariat',
        'actif' => '1',
        'email' => $cible->email,
    ], $surcharge);
}

it('affiche un champ e-mail dans les formulaires de création et de modification', function () {
    $cible = compteEmail('cible-form');

    $this->actingAs(adminEmail())->get(route('comptes.create'))
        ->assertOk()->assertSee('E-mail')->assertSee('name="email"', false);

    $this->actingAs(adminEmail())->get(route('comptes.edit', $cible))
        ->assertOk()->assertSee('E-mail')->assertSee('name="email"', false);
});

it('enregistre l\'e-mail en minuscules et sans espaces', function () {
    $this->actingAs(adminEmail())
        ->post(route('comptes.store'), donneesEmail(['email' => '  Koffi@Exemple.COM ']))
        ->assertSessionHasNoErrors();

    expect(User::where('identifiant', 'koffi')->first()->email)->toBe('koffi@exemple.com');
});

it('crée un compte sans e-mail', function () {
    $this->actingAs(adminEmail())
        ->post(route('comptes.store'), donneesEmail(['email' => '']))
        ->assertSessionHasNoErrors();

    expect(User::where('identifiant', 'koffi')->first()->email)->toBeNull();
});

it('accepte plusieurs comptes sans e-mail', function () {
    $this->actingAs(adminEmail());

    $this->post(route('comptes.store'), donneesEmail(['identifiant' => 'un', 'email' => '']))->assertSessionHasNoErrors();
    $this->post(route('comptes.store'), donneesEmail(['identifiant' => 'deux', 'email' => '']))->assertSessionHasNoErrors();

    expect(User::whereNull('email')->count())->toBeGreaterThanOrEqual(2);
});

it('refuse un e-mail mal écrit', function () {
    $this->actingAs(adminEmail())
        ->post(route('comptes.store'), donneesEmail(['email' => 'pas-un-email']))
        ->assertSessionHasErrors('email');

    expect(User::where('identifiant', 'koffi')->exists())->toBeFalse();
});

it('refuse un e-mail trop long', function () {
    $this->actingAs(adminEmail())
        ->post(route('comptes.store'), donneesEmail(['email' => str_repeat('a', 250).'@x.com']))
        ->assertSessionHasErrors('email');
});

it('refuse un e-mail déjà utilisé, quelle que soit la casse', function () {
    compteEmail('autre', 'koffi@exemple.com');

    $this->actingAs(adminEmail())
        ->post(route('comptes.store'), donneesEmail(['email' => 'KOFFI@exemple.com']))
        ->assertSessionHasErrors('email');
});

it('permet de garder son propre e-mail lors d\'une modification', function () {
    $cible = compteEmail('cible-garde', 'garde@exemple.com');

    $this->actingAs(adminEmail())
        ->put(route('comptes.update', $cible), modifEmail($cible, ['name' => 'Nouveau Nom']))
        ->assertSessionHasNoErrors();

    expect($cible->fresh()->email)->toBe('garde@exemple.com');
});

it('refuse de prendre l\'e-mail d\'un autre compte lors d\'une modification', function () {
    compteEmail('premier', 'pris@exemple.com');
    $cible = compteEmail('second', 'second@exemple.com');

    $this->actingAs(adminEmail())
        ->put(route('comptes.update', $cible), modifEmail($cible, ['email' => 'pris@exemple.com']))
        ->assertSessionHasErrors('email');

    expect($cible->fresh()->email)->toBe('second@exemple.com');
});

it('permet de vider l\'e-mail d\'un compte', function () {
    $cible = compteEmail('cible-vide', 'vide@exemple.com');

    $this->actingAs(adminEmail())
        ->put(route('comptes.update', $cible), modifEmail($cible, ['email' => '']))
        ->assertSessionHasNoErrors();

    expect($cible->fresh()->email)->toBeNull();
});
