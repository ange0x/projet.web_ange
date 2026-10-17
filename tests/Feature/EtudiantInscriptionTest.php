<?php

use App\Enums\Role;
use App\Models\Etudiant;
use App\Models\User;
use App\Support\AcademicYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function utilisateurAdmin(): User
{
    return User::create([
        'name' => 'Administrateur Test',
        'identifiant' => 'admin-etudiants',
        'password' => 'MotDePasse-2026',
        'role' => Role::Administrateur,
        'actif' => true,
    ]);
}

function utilisateurSecretariat(): User
{
    return User::create([
        'name' => 'Awa Secrétaire',
        'identifiant' => 'secretariat-etudiants',
        'password' => 'MotDePasse-2026',
        'role' => Role::Secretariat,
        'actif' => true,
    ]);
}

function donneesEtudiant(array $surcharge = []): array
{
    return array_merge([
        'nom' => 'Adjovi',
        'prenom' => 'Koffi',
        'sexe' => 'masculin',
        'date_naissance' => '2005-03-12',
        'lieu_naissance' => 'Cotonou',
        'adresse' => 'Quartier Akpakpa',
        'telephone' => '0197000000',
        'email' => 'koffi@exemple.com',
        'filiere' => 'Gestion',
        'niveau' => 'Licence 1',
        'annee_academique' => AcademicYear::current(),
        'date_inscription' => now()->toDateString(),
        'tuteur_nom' => 'Mme Adjovi',
        'tuteur_telephone' => '0197000001',
    ], $surcharge);
}

// Accès

it('renvoie un visiteur non connecté vers la page de connexion', function () {
    $this->get(route('etudiants.create'))->assertRedirect(route('connexion'));
    $this->post(route('etudiants.store'), donneesEtudiant())->assertRedirect(route('connexion'));

    expect(Etudiant::count())->toBe(0);
});

it('permet au secrétariat et à l\'administrateur d\'enregistrer un étudiant', function () {
    $this->actingAs(utilisateurSecretariat())
        ->post(route('etudiants.store'), donneesEtudiant())
        ->assertRedirect(route('etudiants.create'));

    $this->actingAs(utilisateurAdmin())
        ->post(route('etudiants.store'), donneesEtudiant([
            'email' => 'autre@exemple.com',
            'nom' => 'Dossou',
        ]))
        ->assertRedirect(route('etudiants.create'));

    expect(Etudiant::count())->toBe(2);
});

it('affiche un lien vers l\'inscription dans l\'accueil', function () {
    $this->actingAs(utilisateurSecretariat())
        ->get(route('accueil'))
        ->assertSee('Enregistrer un étudiant');
});

// Formulaire

it('affiche le formulaire avec des champs étiquetés et des listes de choix', function () {
    $this->actingAs(utilisateurSecretariat())
        ->get(route('etudiants.create'))
        ->assertOk()
        ->assertSee('Nom')
        ->assertSee('Prénom')
        ->assertSee('Sexe')
        ->assertSee('Date de naissance')
        ->assertSee('Lieu de naissance')
        ->assertSee('Adresse')
        ->assertSee('Téléphone')
        ->assertSee('E-mail')
        ->assertSee('Filière')
        ->assertSee('Niveau')
        ->assertSee('Année académique')
        ->assertSee('Date d’inscription')
        ->assertSee('Nom du tuteur')
        ->assertSee('Téléphone du tuteur')
        ->assertSee('Informatique de gestion')
        ->assertSee('Licence 1')
        ->assertSee('Master 2')
        ->assertSee(AcademicYear::current())
        ->assertSee('value="'.now()->toDateString().'"', false)
        ->assertDontSee('name="numero_inscription"', false);
});

// Enregistrement et numéro d'inscription (US4, US5)

it('enregistre l\'étudiant et confirme avec le numéro d\'inscription', function () {
    $this->actingAs(utilisateurSecretariat())
        ->post(route('etudiants.store'), donneesEtudiant())
        ->assertRedirect(route('etudiants.create'))
        ->assertSessionHas('statut');

    $etudiant = Etudiant::firstOrFail();

    expect($etudiant->nom)->toBe('Adjovi')
        ->and($etudiant->prenom)->toBe('Koffi')
        ->and($etudiant->filiere)->toBe('Gestion')
        ->and($etudiant->niveau)->toBe('Licence 1')
        ->and($etudiant->email)->toBe('koffi@exemple.com');

    $this->get(route('etudiants.create'))
        ->assertSee($etudiant->numero_inscription)
        ->assertSee('role="status"', false);
});

it('attribue automatiquement des numéros d\'inscription consécutifs', function () {
    $this->actingAs(utilisateurSecretariat());

    $this->post(route('etudiants.store'), donneesEtudiant());
    $this->post(route('etudiants.store'), donneesEtudiant(['nom' => 'Dossou', 'email' => 'dossou@exemple.com']));

    $annee = now()->year;

    expect(Etudiant::orderBy('id')->pluck('numero_inscription')->all())
        ->toBe([$annee.'-0001', $annee.'-0002']);
});

it('ignore un numéro d\'inscription ou un identifiant envoyés par l\'utilisateur', function () {
    $this->actingAs(utilisateurSecretariat())
        ->post(route('etudiants.store'), donneesEtudiant([
            'numero_inscription' => '1999-9999',
            'id' => 999,
        ]));

    $etudiant = Etudiant::firstOrFail();

    expect($etudiant->numero_inscription)->toBe(now()->year.'-0001')
        ->and($etudiant->id)->not->toBe(999);
});

it('prépare la recherche sans accents ni majuscules', function () {
    $this->actingAs(utilisateurSecretariat())
        ->post(route('etudiants.store'), donneesEtudiant(['prenom' => 'Élodie', 'nom' => 'HOUNKPÈ']));

    $etudiant = Etudiant::firstOrFail();

    expect($etudiant->prenom_search)->toBe('elodie')
        ->and($etudiant->nom_search)->toBe('hounkpe');
});

// Contrôles de saisie (US6)

it('signale tous les champs obligatoires manquants', function () {
    $this->actingAs(utilisateurSecretariat())
        ->post(route('etudiants.store'), [])
        ->assertSessionHasErrors([
            'nom', 'prenom', 'sexe', 'date_naissance', 'telephone',
            'filiere', 'niveau', 'annee_academique', 'date_inscription',
        ]);

    expect(Etudiant::count())->toBe(0);
});

it('enregistre un étudiant sans les champs facultatifs', function () {
    $this->actingAs(utilisateurSecretariat())
        ->post(route('etudiants.store'), donneesEtudiant([
            'lieu_naissance' => '',
            'adresse' => '',
            'email' => '',
            'tuteur_nom' => '',
            'tuteur_telephone' => '',
        ]))
        ->assertSessionHasNoErrors();

    $etudiant = Etudiant::firstOrFail();

    expect($etudiant->email)->toBeNull()
        ->and($etudiant->tuteur_nom)->toBeNull();
});

it('accepte les téléphones à 10 chiffres commençant par 01, avec ou sans +229', function (string $saisie) {
    $this->actingAs(utilisateurSecretariat())
        ->post(route('etudiants.store'), donneesEtudiant(['telephone' => $saisie]))
        ->assertSessionHasNoErrors();

    expect(Etudiant::firstOrFail()->telephone)->toBe('0197000000');
})->with([
    '0197000000',
    '01 97 00 00 00',
    '01-97-00-00-00',
    '+229 01 97 00 00 00',
    '+22901 97000000',
]);

it('refuse les téléphones invalides', function (string $saisie) {
    $this->actingAs(utilisateurSecretariat())
        ->post(route('etudiants.store'), donneesEtudiant(['telephone' => $saisie]))
        ->assertSessionHasErrors('telephone');

    expect(Etudiant::count())->toBe(0);
})->with([
    '0297000000',
    '019700000',
    '01970000001',
    '+33 01 97 00 00 00',
    '+229 97 00 00 00',
    'abcdefghij',
    '01 97 00 00 0a',
]);

it('applique la même règle au téléphone du tuteur', function () {
    $this->actingAs(utilisateurSecretariat());

    $this->post(route('etudiants.store'), donneesEtudiant(['tuteur_telephone' => '12345']))
        ->assertSessionHasErrors('tuteur_telephone');

    $this->post(route('etudiants.store'), donneesEtudiant(['tuteur_telephone' => '+229 01 97 00 00 01']))
        ->assertSessionHasNoErrors();

    expect(Etudiant::firstOrFail()->tuteur_telephone)->toBe('0197000001');
});

it('refuse un e-mail mal écrit', function () {
    $this->actingAs(utilisateurSecretariat())
        ->post(route('etudiants.store'), donneesEtudiant(['email' => 'pas-un-email']))
        ->assertSessionHasErrors('email');
});

it('refuse une date de naissance dans le futur ou illisible', function () {
    $this->actingAs(utilisateurSecretariat());

    $this->post(route('etudiants.store'), donneesEtudiant(['date_naissance' => now()->addDay()->toDateString()]))
        ->assertSessionHasErrors('date_naissance');

    $this->post(route('etudiants.store'), donneesEtudiant(['date_naissance' => 'pas une date']))
        ->assertSessionHasErrors('date_naissance');

    expect(Etudiant::count())->toBe(0);
});

it('refuse une date d\'inscription dans le futur', function () {
    $this->actingAs(utilisateurSecretariat())
        ->post(route('etudiants.store'), donneesEtudiant(['date_inscription' => now()->addDay()->toDateString()]))
        ->assertSessionHasErrors('date_inscription');
});

it('refuse une filière, un niveau ou un sexe hors des listes', function () {
    $this->actingAs(utilisateurSecretariat())
        ->post(route('etudiants.store'), donneesEtudiant([
            'filiere' => 'Astrologie',
            'niveau' => 'Doctorat 9',
            'sexe' => 'autre',
        ]))
        ->assertSessionHasErrors(['filiere', 'niveau', 'sexe']);
});

it('refuse une année académique mal formée', function (string $annee) {
    $this->actingAs(utilisateurSecretariat())
        ->post(route('etudiants.store'), donneesEtudiant(['annee_academique' => $annee]))
        ->assertSessionHasErrors('annee_academique');
})->with(['2026', '2026-2028', '26-27', 'abcd-efgh']);

it('refuse les textes trop longs', function () {
    $this->actingAs(utilisateurSecretariat())
        ->post(route('etudiants.store'), donneesEtudiant([
            'nom' => str_repeat('a', 101),
            'adresse' => str_repeat('b', 256),
        ]))
        ->assertSessionHasErrors(['nom', 'adresse']);
});

// Doublons (US7)

it('refuse un e-mail déjà utilisé, quelle que soit la casse', function () {
    $this->actingAs(utilisateurSecretariat());

    $this->post(route('etudiants.store'), donneesEtudiant());

    $this->post(route('etudiants.store'), donneesEtudiant([
        'nom' => 'Dossou',
        'prenom' => 'Awa',
        'email' => 'KOFFI@exemple.com',
    ]))->assertSessionHasErrors('email');

    expect(Etudiant::count())->toBe(1);
});

it('refuse un étudiant qui a les mêmes nom, prénom et date de naissance', function () {
    $this->actingAs(utilisateurSecretariat());

    $this->post(route('etudiants.store'), donneesEtudiant());

    $this->post(route('etudiants.store'), donneesEtudiant([
        'nom' => 'ADJOVI',
        'prenom' => 'koffi',
        'email' => 'autre@exemple.com',
    ]))->assertSessionHasErrors('nom');

    expect(Etudiant::count())->toBe(1);
});

it('accepte deux étudiants qui ont seulement le même téléphone', function () {
    $this->actingAs(utilisateurSecretariat());

    $this->post(route('etudiants.store'), donneesEtudiant());
    $this->post(route('etudiants.store'), donneesEtudiant([
        'nom' => 'Dossou',
        'prenom' => 'Awa',
        'email' => 'awa@exemple.com',
    ]))->assertSessionHasNoErrors();

    expect(Etudiant::count())->toBe(2);
});

// Sécurité (OWASP)

it('échappe les textes saisis quand le formulaire est réaffiché', function () {
    $this->actingAs(utilisateurSecretariat());

    $this->from(route('etudiants.create'))
        ->post(route('etudiants.store'), ['nom' => '<script>alert(1)</script>']);

    $this->get(route('etudiants.create'))
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;', false);
});

// Accessibilité (WCAG 2.2 AA)

it('annonce les erreurs et marque les champs invalides', function () {
    $this->actingAs(utilisateurSecretariat());

    $this->from(route('etudiants.create'))->post(route('etudiants.store'), []);

    $this->get(route('etudiants.create'))
        ->assertSee('role="alert"', false)
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('Veuillez corriger les erreurs suivantes');
});

// Journal d'audit

it('enregistre la création dans le journal avec son auteur', function () {
    $secretaire = utilisateurSecretariat();

    $this->actingAs($secretaire)->post(route('etudiants.store'), donneesEtudiant());

    $nombre = DB::table('activity_log')
        ->where('log_name', 'etudiants')
        ->where('event', 'created')
        ->where('causer_id', $secretaire->id)
        ->count();

    expect($nombre)->toBe(1);
});
