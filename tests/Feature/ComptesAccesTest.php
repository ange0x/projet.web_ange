<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function acteurAcces(Role $role = Role::Administrateur): User
{
    return User::firstOrCreate(
        ['identifiant' => 'acces-'.$role->value],
        [
            'name' => 'Acteur Acces',
            'password' => 'MotDePasse-2026',
            'role' => $role,
            'actif' => true,
        ],
    );
}

it('renvoie vers l\'accueil un utilisateur déjà connecté qui ouvre la page de connexion', function () {
    $this->actingAs(acteurAcces(Role::Secretariat))
        ->get(route('connexion'))
        ->assertRedirect(route('accueil'));
});

it('répond 404 pour un compte qui n\'existe pas', function () {
    $this->actingAs(acteurAcces())
        ->get(route('comptes.edit', 99999))
        ->assertNotFound();
});

it('ne propose aucune suppression de compte', function () {
    $admin = acteurAcces();
    $secretaire = acteurAcces(Role::Secretariat);

    $this->actingAs($admin)
        ->delete('/comptes/'.$secretaire->id)
        ->assertStatus(405);

    expect(User::whereKey($secretaire->id)->exists())->toBeTrue();
});
