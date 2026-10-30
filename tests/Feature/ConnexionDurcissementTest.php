<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function compteDurcissement(): User
{
    return User::firstOrCreate(
        ['identifiant' => 'durcissement-test'],
        [
            'name' => 'Compte Durcissement',
            'password' => 'MotDePasse-2026',
            'role' => Role::Secretariat,
            'actif' => true,
        ],
    );
}

// R12 : limite de tentatives

it('limite à 10 tentatives par minute la route de connexion', function () {
    foreach (range(1, 10) as $i) {
        $this->post(route('connexion.soumettre'), ['identifiant' => 'inconnu-'.$i, 'password' => 'x'])
            ->assertRedirect();
    }

    $this->post(route('connexion.soumettre'), ['identifiant' => 'inconnu-11', 'password' => 'x'])
        ->assertStatus(429)
        ->assertHeader('Retry-After');
});

// R15 : journal des échecs

it('n\'écrit pas le texte saisi dans le journal quand l\'identifiant est inconnu', function () {
    $this->post(route('connexion.soumettre'), ['identifiant' => 'SecretTape-2026', 'password' => 'x']);

    expect(json_encode(DB::table('activity_log')->get()))->not->toContain('SecretTape-2026')
        ->and(DB::table('activity_log')->where('log_name', 'auth')->where('event', 'login_failed')->count())->toBe(1);
});

it('enregistre l\'identifiant d\'un échec quand il correspond à un compte existant', function () {
    compteDurcissement();

    $this->post(route('connexion.soumettre'), ['identifiant' => 'durcissement-test', 'password' => 'faux']);

    $ligne = DB::table('activity_log')->where('event', 'login_failed')->first();

    expect($ligne->properties)->toContain('durcissement-test');
});

it('enregistre l\'adresse IP même pour un identifiant inconnu', function () {
    $this->post(route('connexion.soumettre'), ['identifiant' => 'quelquun', 'password' => 'x']);

    $ligne = DB::table('activity_log')->where('event', 'login_failed')->first();

    expect($ligne->properties)->toContain('ip');
});
