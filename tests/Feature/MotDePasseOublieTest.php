<?php

use App\Enums\Role;
use App\Models\User;
use App\Notifications\ReinitialisationMotDePasse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

const MESSAGE_NEUTRE = 'Si cette adresse correspond à un compte actif';

function compteMdp(array $attributs = []): User
{
    return User::create(array_merge([
        'name' => 'Awa Secrétaire',
        'identifiant' => 'awa-mdp',
        'email' => 'awa@exemple.com',
        'password' => 'AncienMotDePasse-2026',
        'role' => Role::Secretariat,
        'actif' => true,
    ], $attributs));
}

function demanderLien(string $email)
{
    return test()->post(route('mot-de-passe.envoyer'), ['email' => $email]);
}

function reinitialiser(array $donnees)
{
    return test()->post(route('mot-de-passe.reinitialiser'), array_merge([
        'password' => 'NouveauMotDePasse-2026',
        'password_confirmation' => 'NouveauMotDePasse-2026',
    ], $donnees));
}

// R17 : la demande

it('affiche la page de demande avec un champ étiqueté', function () {
    $this->get(route('mot-de-passe.demande'))
        ->assertOk()
        ->assertSee('Mot de passe oublié')
        ->assertSee('E-mail')
        ->assertSee('Envoyer le lien')
        ->assertSee('Retour à la connexion');
});

it('affiche le lien sur la page de connexion', function () {
    $this->get(route('connexion'))
        ->assertSee('Mot de passe oublié ?')
        ->assertSee(route('mot-de-passe.demande'));
});

it('renvoie vers l\'accueil un utilisateur déjà connecté', function () {
    $this->actingAs(compteMdp())
        ->get(route('mot-de-passe.demande'))
        ->assertRedirect(route('accueil'));
});

it('envoie le lien à un compte actif avec e-mail', function () {
    Notification::fake();
    $user = compteMdp();

    demanderLien('awa@exemple.com')
        ->assertRedirect(route('mot-de-passe.demande'))
        ->assertSessionHas('statut', fn ($m) => str_contains($m, MESSAGE_NEUTRE));

    Notification::assertSentTo($user, ReinitialisationMotDePasse::class);
});

it('accepte une adresse en majuscules et avec des espaces', function () {
    Notification::fake();
    $user = compteMdp();

    demanderLien('  AWA@Exemple.COM ');

    Notification::assertSentTo($user, ReinitialisationMotDePasse::class);
});

it('donne le même message pour une adresse inconnue, sans rien envoyer', function () {
    Notification::fake();
    compteMdp();

    demanderLien('inconnu@exemple.com')
        ->assertRedirect(route('mot-de-passe.demande'))
        ->assertSessionHas('statut', fn ($m) => str_contains($m, MESSAGE_NEUTRE));

    Notification::assertNothingSent();
    expect(DB::table('password_reset_tokens')->count())->toBe(0);
});

it('donne le même message pour un compte désactivé, sans rien envoyer', function () {
    Notification::fake();
    compteMdp(['actif' => false]);

    demanderLien('awa@exemple.com')
        ->assertSessionHas('statut', fn ($m) => str_contains($m, MESSAGE_NEUTRE));

    Notification::assertNothingSent();
});

it('donne le même message pour un compte archivé, sans rien envoyer', function () {
    Notification::fake();
    compteMdp()->delete();

    demanderLien('awa@exemple.com')
        ->assertSessionHas('statut', fn ($m) => str_contains($m, MESSAGE_NEUTRE));

    Notification::assertNothingSent();
});

it('refuse une adresse vide ou mal écrite', function () {
    $this->post(route('mot-de-passe.envoyer'), ['email' => ''])->assertSessionHasErrors('email');
    $this->post(route('mot-de-passe.envoyer'), ['email' => 'pas-un-email'])->assertSessionHasErrors('email');
});

it('affiche le message de confirmation sur la page de demande', function () {
    Notification::fake();
    compteMdp();

    demanderLien('awa@exemple.com');

    $this->get(route('mot-de-passe.demande'))
        ->assertSee('role="status"', false)
        ->assertSee(MESSAGE_NEUTRE);
});

// R18 : le jeton

it('stocke le jeton haché et construit un lien de réinitialisation', function () {
    Notification::fake();
    $user = compteMdp();

    demanderLien('awa@exemple.com');

    Notification::assertSentTo($user, ReinitialisationMotDePasse::class, function ($notification) use ($user) {
        $lien = $notification->toMail($user)->actionUrl;
        $ligne = DB::table('password_reset_tokens')->where('email', 'awa@exemple.com')->first();

        return str_contains($lien, '/reinitialisation/'.$notification->token)
            && str_contains($lien, 'email=')
            && $ligne !== null
            && $ligne->token !== $notification->token
            && str_starts_with($ligne->token, '$2y$');
    });
});

it('remplace l\'ancien jeton par un nouveau', function () {
    Notification::fake();
    compteMdp();

    demanderLien('awa@exemple.com');
    $this->travel(2)->minutes();
    demanderLien('awa@exemple.com');

    expect(DB::table('password_reset_tokens')->where('email', 'awa@exemple.com')->count())->toBe(1);
    Notification::assertSentToTimes(compteMdpExistant(), ReinitialisationMotDePasse::class, 2);
});

function compteMdpExistant(): User
{
    return User::where('identifiant', 'awa-mdp')->firstOrFail();
}

it('n\'envoie qu\'un lien par minute pour un même compte', function () {
    Notification::fake();
    $user = compteMdp();

    demanderLien('awa@exemple.com');
    demanderLien('awa@exemple.com');

    Notification::assertSentToTimes($user, ReinitialisationMotDePasse::class, 1);
});

// R19 : limite par adresse IP

it('limite à 5 demandes par heure et par adresse IP', function () {
    Notification::fake();

    foreach (range(1, 5) as $i) {
        demanderLien('inconnu'.$i.'@exemple.com')->assertRedirect();
    }

    demanderLien('inconnu6@exemple.com')->assertStatus(429);
});

// Le formulaire de réinitialisation

it('affiche le formulaire avec le jeton et deux champs de mot de passe', function () {
    $this->get(route('password.reset', ['token' => 'jeton-test', 'email' => 'awa@exemple.com']))
        ->assertOk()
        ->assertSee('Nouveau mot de passe')
        ->assertSee('Confirmation du mot de passe')
        ->assertSee('name="token" value="jeton-test"', false)
        ->assertSee('awa@exemple.com')
        ->assertSee('autocomplete="new-password"', false);
});

it('échappe l\'adresse reçue dans le lien', function () {
    $this->get(route('password.reset', ['token' => 'x', 'email' => '"><script>alert(1)</script>']))
        ->assertDontSee('<script>alert(1)</script>', false);
});

// R20 et R21 : la réinitialisation

it('change le mot de passe avec un jeton valide, sans connecter', function () {
    $user = compteMdp();
    $jeton = Password::broker()->createToken($user);

    reinitialiser(['email' => 'awa@exemple.com', 'token' => $jeton])
        ->assertRedirect(route('connexion'))
        ->assertSessionHas('statut');

    $this->assertGuest();
    expect(Hash::check('NouveauMotDePasse-2026', $user->fresh()->password))->toBeTrue()
        ->and(Hash::check('AncienMotDePasse-2026', $user->fresh()->password))->toBeFalse();
});

it('permet ensuite de se connecter avec le nouveau mot de passe seulement', function () {
    $user = compteMdp();
    $jeton = Password::broker()->createToken($user);

    reinitialiser(['email' => 'awa@exemple.com', 'token' => $jeton]);

    $this->post(route('connexion.soumettre'), ['identifiant' => 'awa-mdp', 'password' => 'AncienMotDePasse-2026'])
        ->assertSessionHasErrors('identifiant');
    $this->assertGuest();

    $this->post(route('connexion.soumettre'), ['identifiant' => 'awa-mdp', 'password' => 'NouveauMotDePasse-2026'])
        ->assertRedirect(route('accueil'));
    $this->assertAuthenticated();
});

it('affiche la confirmation sur la page de connexion', function () {
    $user = compteMdp();
    $jeton = Password::broker()->createToken($user);

    reinitialiser(['email' => 'awa@exemple.com', 'token' => $jeton]);

    $this->get(route('connexion'))
        ->assertSee('role="status"', false)
        ->assertSee('Votre mot de passe a été modifié');
});

it('ne permet d\'utiliser un jeton qu\'une seule fois', function () {
    $user = compteMdp();
    $jeton = Password::broker()->createToken($user);

    reinitialiser(['email' => 'awa@exemple.com', 'token' => $jeton]);
    reinitialiser([
        'email' => 'awa@exemple.com',
        'token' => $jeton,
        'password' => 'AutreMotDePasse-2026',
        'password_confirmation' => 'AutreMotDePasse-2026',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('NouveauMotDePasse-2026', $user->fresh()->password))->toBeTrue();
});

it('refuse un jeton expiré après 60 minutes', function () {
    $user = compteMdp();
    $jeton = Password::broker()->createToken($user);

    $this->travel(61)->minutes();

    reinitialiser(['email' => 'awa@exemple.com', 'token' => $jeton])->assertSessionHasErrors('email');

    expect(Hash::check('AncienMotDePasse-2026', $user->fresh()->password))->toBeTrue();
});

it('accepte encore le jeton juste avant l\'expiration', function () {
    $user = compteMdp();
    $jeton = Password::broker()->createToken($user);

    $this->travel(59)->minutes();

    reinitialiser(['email' => 'awa@exemple.com', 'token' => $jeton])->assertRedirect(route('connexion'));
});

it('refuse un jeton falsifié', function () {
    $user = compteMdp();
    Password::broker()->createToken($user);

    reinitialiser(['email' => 'awa@exemple.com', 'token' => 'jeton-falsifie'])->assertSessionHasErrors('email');

    expect(Hash::check('AncienMotDePasse-2026', $user->fresh()->password))->toBeTrue();
});

it('refuse un jeton valide associé à une autre adresse', function () {
    $user = compteMdp();
    compteMdp(['identifiant' => 'autre-mdp', 'email' => 'autre@exemple.com']);
    $jeton = Password::broker()->createToken($user);

    reinitialiser(['email' => 'autre@exemple.com', 'token' => $jeton])->assertSessionHasErrors('email');

    expect(Hash::check('AncienMotDePasse-2026', $user->fresh()->password))->toBeTrue();
});

it('donne le même message pour un jeton invalide et une adresse inconnue', function () {
    $user = compteMdp();
    $jeton = Password::broker()->createToken($user);

    reinitialiser(['email' => 'awa@exemple.com', 'token' => 'faux'])
        ->assertSessionHasErrors(['email' => 'Ce lien est invalide ou a expiré. Faites une nouvelle demande.']);

    reinitialiser(['email' => 'inconnu@exemple.com', 'token' => $jeton])
        ->assertSessionHasErrors(['email' => 'Ce lien est invalide ou a expiré. Faites une nouvelle demande.']);
});

it('refuse un mot de passe de moins de 8 caractères et garde le jeton utilisable', function () {
    $user = compteMdp();
    $jeton = Password::broker()->createToken($user);

    reinitialiser([
        'email' => 'awa@exemple.com',
        'token' => $jeton,
        'password' => 'court',
        'password_confirmation' => 'court',
    ])->assertSessionHasErrors('password');

    expect(Hash::check('AncienMotDePasse-2026', $user->fresh()->password))->toBeTrue();

    reinitialiser(['email' => 'awa@exemple.com', 'token' => $jeton])->assertRedirect(route('connexion'));
});

it('refuse une confirmation différente', function () {
    $user = compteMdp();
    $jeton = Password::broker()->createToken($user);

    reinitialiser([
        'email' => 'awa@exemple.com',
        'token' => $jeton,
        'password_confirmation' => 'Different-2026-Pw',
    ])->assertSessionHasErrors('password');

    expect(Hash::check('AncienMotDePasse-2026', $user->fresh()->password))->toBeTrue();
});

it('refuse la réinitialisation d\'un compte désactivé même avec un jeton valide', function () {
    $user = compteMdp();
    $jeton = Password::broker()->createToken($user);
    $user->forceFill(['actif' => false])->save();

    reinitialiser(['email' => 'awa@exemple.com', 'token' => $jeton])->assertSessionHasErrors('email');

    expect(Hash::check('AncienMotDePasse-2026', $user->fresh()->password))->toBeTrue();
});

it('débloque le compte à la réinitialisation', function () {
    $user = compteMdp();
    $user->forceFill(['failed_login_attempts' => 5, 'locked_until' => now()->addMinutes(15)])->save();
    $jeton = Password::broker()->createToken($user);

    reinitialiser(['email' => 'awa@exemple.com', 'token' => $jeton]);

    $user->refresh();

    expect($user->failed_login_attempts)->toBe(0)
        ->and($user->locked_until)->toBeNull();

    $this->post(route('connexion.soumettre'), ['identifiant' => 'awa-mdp', 'password' => 'NouveauMotDePasse-2026'])
        ->assertRedirect(route('accueil'));
});

it('ferme les sessions ouvertes du compte, et seulement les siennes', function () {
    $user = compteMdp();
    $autre = compteMdp(['identifiant' => 'autre-mdp', 'email' => 'autre@exemple.com']);

    foreach ([[$user->id, 'session-a'], [$user->id, 'session-b'], [$autre->id, 'session-c']] as [$id, $cle]) {
        DB::table('sessions')->insert([
            'id' => $cle,
            'user_id' => $id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'x',
            'last_activity' => time(),
        ]);
    }

    $jeton = Password::broker()->createToken($user);
    reinitialiser(['email' => 'awa@exemple.com', 'token' => $jeton]);

    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0)
        ->and(DB::table('sessions')->where('user_id', $autre->id)->count())->toBe(1);
});

it('journalise la réinitialisation sans mot de passe ni jeton', function () {
    $user = compteMdp();
    $jeton = Password::broker()->createToken($user);

    reinitialiser(['email' => 'awa@exemple.com', 'token' => $jeton]);

    $nombre = DB::table('activity_log')
        ->where('log_name', 'comptes')
        ->where('event', 'password_reset')
        ->where('subject_id', $user->id)
        ->count();

    $journal = json_encode(DB::table('activity_log')->get());

    expect($nombre)->toBe(1)
        ->and($journal)->not->toContain('NouveauMotDePasse-2026')
        ->and($journal)->not->toContain($jeton)
        ->and($journal)->not->toContain('$2y$');
});
