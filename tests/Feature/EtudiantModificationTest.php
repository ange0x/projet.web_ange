<?php

use App\Enums\Role;
use App\Models\Etudiant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function utilisateurModif(Role $role = Role::Secretariat): User
{
    return User::firstOrCreate(
        ['identifiant' => 'modif-'.$role->value],
        [
            'name' => 'Utilisateur Modif',
            'password' => 'MotDePasse-2026',
            'role' => $role,
            'actif' => true,
        ],
    );
}

function etudiantModif(array $attributs = []): Etudiant
{
    return Etudiant::factory()->create(array_merge([
        'numero_inscription' => '2026-0042',
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
        'annee_academique' => '2026-2027',
        'date_inscription' => '2026-09-14',
        'tuteur_nom' => 'Mme Adjovi',
        'tuteur_telephone' => '0197000001',
    ], $attributs));
}

function autreEtudiantModif(array $attributs = []): Etudiant
{
    return etudiantModif(array_merge([
        'numero_inscription' => '2026-0043',
        'nom' => 'Dossou',
        'prenom' => 'Awa',
        'date_naissance' => '2004-01-01',
        'telephone' => '0197000002',
        'email' => 'awa@exemple.com',
        'tuteur_nom' => null,
        'tuteur_telephone' => null,
    ], $attributs));
}

function donneesModif(array $surcharge = []): array
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
        'annee_academique' => '2026-2027',
        'date_inscription' => '2026-09-14',
        'tuteur_nom' => 'Mme Adjovi',
        'tuteur_telephone' => '0197000001',
    ], $surcharge);
}

// Accès

it('renvoie un visiteur non connecté vers la page de connexion', function () {
    $etudiant = etudiantModif();

    $this->get(route('etudiants.edit', $etudiant))->assertRedirect(route('connexion'));
    $this->post(route('etudiants.confirmer', $etudiant), donneesModif(['nom' => 'Autre']))->assertRedirect(route('connexion'));
    $this->post(route('etudiants.corriger', $etudiant), donneesModif())->assertRedirect(route('connexion'));
    $this->put(route('etudiants.update', $etudiant), donneesModif(['nom' => 'Autre']))->assertRedirect(route('connexion'));

    expect($etudiant->fresh()->nom)->toBe('Adjovi');
});

it('permet au secrétariat et à l\'administrateur de modifier un étudiant', function () {
    $etudiant = etudiantModif();

    $this->actingAs(utilisateurModif(Role::Secretariat))
        ->get(route('etudiants.edit', $etudiant))
        ->assertOk();

    $this->actingAs(utilisateurModif(Role::Administrateur))
        ->put(route('etudiants.update', $etudiant), donneesModif(['nom' => 'Dossou']))
        ->assertRedirect(route('etudiants.show', $etudiant));

    expect($etudiant->fresh()->nom)->toBe('Dossou');
});

it('propose un lien de modification dans la fiche', function () {
    $etudiant = etudiantModif();

    $this->actingAs(utilisateurModif())
        ->get(route('etudiants.show', $etudiant))
        ->assertSee('Modifier la fiche')
        ->assertSee(route('etudiants.edit', $etudiant));
});

// US13 : le formulaire

it('affiche le formulaire de modification pré-rempli', function () {
    $etudiant = etudiantModif();

    $this->actingAs(utilisateurModif())
        ->get(route('etudiants.edit', $etudiant))
        ->assertOk()
        ->assertSee('value="Adjovi"', false)
        ->assertSee('value="Koffi"', false)
        ->assertSee('value="Cotonou"', false)
        ->assertSee('value="0197000000"', false)
        ->assertSee('value="koffi@exemple.com"', false)
        ->assertSee('value="2005-03-12"', false)
        ->assertSee('value="2026-09-14"', false)
        ->assertSee('value="masculin" selected', false)
        ->assertSee('value="Gestion" selected', false)
        ->assertSee('value="Licence 1" selected', false)
        ->assertSee('Vérifier les modifications')
        ->assertSee(route('etudiants.confirmer', $etudiant))
        ->assertSee('Annuler');
});

it('affiche le numéro d\'inscription sans permettre de le modifier', function () {
    $etudiant = etudiantModif();

    $this->actingAs(utilisateurModif())
        ->get(route('etudiants.edit', $etudiant))
        ->assertSee('2026-0042')
        ->assertSee('non modifiable')
        ->assertDontSee('name="numero_inscription"', false);
});

it('échappe les textes dans le formulaire de modification', function () {
    $etudiant = etudiantModif(['nom' => '<script>alert(1)</script>']);

    $this->actingAs(utilisateurModif())
        ->get(route('etudiants.edit', $etudiant))
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;', false);
});

it('annonce les erreurs et marque les champs invalides', function () {
    $etudiant = etudiantModif();

    $this->actingAs(utilisateurModif())
        ->post(route('etudiants.confirmer', $etudiant), donneesModif(['nom' => '']))
        ->assertRedirect(route('etudiants.edit', $etudiant))
        ->assertSessionHasErrors('nom');

    $this->actingAs(utilisateurModif())
        ->followingRedirects()
        ->post(route('etudiants.confirmer', $etudiant), donneesModif(['nom' => '']))
        ->assertOk()
        ->assertSee('role="alert"', false)
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('Veuillez corriger les erreurs suivantes')
        ->assertSee('Saisissez le nom de l’étudiant.');
});

// US14 : la page de confirmation

it('affiche seulement les informations modifiées, avec l\'ancienne et la nouvelle valeur', function () {
    $etudiant = etudiantModif();

    $this->actingAs(utilisateurModif())
        ->post(route('etudiants.confirmer', $etudiant), donneesModif([
            'nom' => 'Dossou',
            'telephone' => '+229 01 55 55 55 55',
        ]))
        ->assertOk()
        ->assertSee('Adjovi')
        ->assertSee('Dossou')
        ->assertSee('0197000000')
        ->assertSee('0155555555')
        ->assertDontSee('Lieu de naissance')
        ->assertDontSee('Date d’inscription');

    expect($etudiant->fresh()->nom)->toBe('Adjovi')
        ->and($etudiant->fresh()->telephone)->toBe('0197000000');
});

it('n\'enregistre rien tant que la modification n\'est pas confirmée', function () {
    $etudiant = etudiantModif();

    $this->actingAs(utilisateurModif())
        ->post(route('etudiants.confirmer', $etudiant), donneesModif(['filiere' => 'Marketing']))
        ->assertOk();

    expect($etudiant->fresh()->filiere)->toBe('Gestion');
});

it('propose de confirmer ou de modifier à nouveau', function () {
    $etudiant = etudiantModif();

    $this->actingAs(utilisateurModif())
        ->post(route('etudiants.confirmer', $etudiant), donneesModif(['nom' => 'Dossou']))
        ->assertSee('Confirmer la modification')
        ->assertSee('Modifier à nouveau')
        ->assertSee('name="_method" value="PUT"', false)
        ->assertSee('name="_token"', false)
        ->assertSee('name="nom" value="Dossou"', false)
        ->assertSee(route('etudiants.update', $etudiant))
        ->assertSee(route('etudiants.corriger', $etudiant));
});

it('présente les changements dans un tableau structuré', function () {
    $etudiant = etudiantModif();

    $this->actingAs(utilisateurModif())
        ->post(route('etudiants.confirmer', $etudiant), donneesModif(['nom' => 'Dossou']))
        ->assertSee('Modifications à confirmer')
        ->assertSee('Ancienne valeur')
        ->assertSee('Nouvelle valeur')
        ->assertSee('<caption', false)
        ->assertSee('scope="col"', false)
        ->assertSee('scope="row"', false);
});

it('affiche « Non renseigné » quand une information est vidée', function () {
    $etudiant = etudiantModif();

    $this->actingAs(utilisateurModif())
        ->post(route('etudiants.confirmer', $etudiant), donneesModif(['adresse' => '']))
        ->assertOk()
        ->assertSee('Quartier Akpakpa')
        ->assertSee('Non renseigné');
});

it('revient à la fiche quand rien n\'a été modifié', function () {
    $etudiant = etudiantModif();

    $this->actingAs(utilisateurModif())
        ->post(route('etudiants.confirmer', $etudiant), donneesModif())
        ->assertRedirect(route('etudiants.show', $etudiant))
        ->assertSessionHas('statut');

    $this->get(route('etudiants.show', $etudiant))->assertSee('Aucune information');
});

it('refuse la confirmation quand les données sont invalides', function () {
    $etudiant = etudiantModif();

    $this->actingAs(utilisateurModif())
        ->post(route('etudiants.confirmer', $etudiant), donneesModif(['nom' => '', 'telephone' => '0297000000']))
        ->assertRedirect(route('etudiants.edit', $etudiant))
        ->assertSessionHasErrors(['nom', 'telephone']);

    expect($etudiant->fresh()->nom)->toBe('Adjovi');
});

it('échappe les textes dans la page de confirmation', function () {
    $etudiant = etudiantModif();

    $this->actingAs(utilisateurModif())
        ->post(route('etudiants.confirmer', $etudiant), donneesModif(['nom' => '<script>alert(1)</script>']))
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;', false);
});

it('revient au formulaire avec ce qui a été saisi', function () {
    $etudiant = etudiantModif();

    $this->actingAs(utilisateurModif())
        ->post(route('etudiants.corriger', $etudiant), donneesModif(['nom' => 'Dossou']))
        ->assertOk()
        ->assertSee('value="Dossou"', false)
        ->assertSee('Vérifier les modifications');

    expect($etudiant->fresh()->nom)->toBe('Adjovi');
});

// Enregistrement

it('enregistre les modifications et confirme', function () {
    $etudiant = etudiantModif();

    $this->actingAs(utilisateurModif())
        ->put(route('etudiants.update', $etudiant), donneesModif([
            'nom' => 'Dossou',
            'telephone' => '+229 01 55 55 55 55',
            'filiere' => 'Marketing',
            'niveau' => 'Master 1',
        ]))
        ->assertRedirect(route('etudiants.show', $etudiant))
        ->assertSessionHas('statut');

    $etudiant->refresh();

    expect($etudiant->nom)->toBe('Dossou')
        ->and($etudiant->telephone)->toBe('0155555555')
        ->and($etudiant->filiere)->toBe('Marketing')
        ->and($etudiant->niveau)->toBe('Master 1')
        ->and($etudiant->prenom)->toBe('Koffi');
});

it('affiche la confirmation dans la fiche après la modification', function () {
    $etudiant = etudiantModif();

    $this->actingAs(utilisateurModif())
        ->put(route('etudiants.update', $etudiant), donneesModif(['nom' => 'Dossou']));

    $this->get(route('etudiants.show', $etudiant))
        ->assertSee('role="status"', false)
        ->assertSee('a été modifiée');
});

it('ignore un numéro d\'inscription ou un identifiant envoyés par l\'utilisateur', function () {
    $etudiant = etudiantModif();

    $this->actingAs(utilisateurModif())
        ->put(route('etudiants.update', $etudiant), donneesModif([
            'nom' => 'Dossou',
            'numero_inscription' => '1999-9999',
            'id' => 999,
        ]));

    $etudiant->refresh();

    expect($etudiant->numero_inscription)->toBe('2026-0042')
        ->and($etudiant->nom)->toBe('Dossou')
        ->and(Etudiant::count())->toBe(1);
});

it('permet de vider les informations facultatives', function () {
    $etudiant = etudiantModif();

    $this->actingAs(utilisateurModif())
        ->put(route('etudiants.update', $etudiant), donneesModif([
            'lieu_naissance' => '',
            'adresse' => '',
            'email' => '',
            'tuteur_nom' => '',
            'tuteur_telephone' => '',
        ]))
        ->assertSessionHasNoErrors();

    $etudiant->refresh();

    expect($etudiant->lieu_naissance)->toBeNull()
        ->and($etudiant->adresse)->toBeNull()
        ->and($etudiant->email)->toBeNull()
        ->and($etudiant->tuteur_nom)->toBeNull()
        ->and($etudiant->tuteur_telephone)->toBeNull();
});

it('remet à jour les champs de recherche', function () {
    $etudiant = etudiantModif();

    $this->actingAs(utilisateurModif())
        ->put(route('etudiants.update', $etudiant), donneesModif(['nom' => 'HOUNKPÈ']));

    $etudiant->refresh();

    expect($etudiant->nom_search)->toBe('hounkpe')
        ->and($etudiant->prenom_search)->toBe('koffi');
});

it('conserve la date et l\'auteur de la dernière modification', function () {
    $etudiant = etudiantModif();
    $utilisateur = utilisateurModif();

    $this->actingAs($utilisateur)
        ->put(route('etudiants.update', $etudiant), donneesModif(['nom' => 'Dossou']));

    expect((int) $etudiant->fresh()->modifie_par_id)->toBe($utilisateur->id);

    $this->get(route('etudiants.show', $etudiant))
        ->assertSee('Dernière modification')
        ->assertSee('Utilisateur Modif');
});

it('enregistre la modification dans le journal avec son auteur', function () {
    $etudiant = etudiantModif();
    $utilisateur = utilisateurModif();

    $this->actingAs($utilisateur)
        ->put(route('etudiants.update', $etudiant), donneesModif(['nom' => 'Dossou']));

    $nombre = DB::table('activity_log')
        ->where('log_name', 'etudiants')
        ->where('event', 'updated')
        ->where('causer_id', $utilisateur->id)
        ->count();

    expect($nombre)->toBe(1);
});

// Validation à l'enregistrement : jamais de confiance aux champs cachés

it('refuse un enregistrement sans les champs obligatoires', function () {
    $etudiant = etudiantModif();

    $this->actingAs(utilisateurModif())
        ->put(route('etudiants.update', $etudiant), [])
        ->assertRedirect(route('etudiants.edit', $etudiant))
        ->assertSessionHasErrors([
            'nom', 'prenom', 'sexe', 'date_naissance', 'telephone',
            'filiere', 'niveau', 'annee_academique', 'date_inscription',
        ]);

    expect($etudiant->fresh()->nom)->toBe('Adjovi');
});

it('refuse des valeurs invalides à l\'enregistrement', function () {
    $etudiant = etudiantModif();

    $this->actingAs(utilisateurModif())
        ->put(route('etudiants.update', $etudiant), donneesModif([
            'telephone' => '0297000000',
            'tuteur_telephone' => '123',
            'date_naissance' => now()->addDay()->toDateString(),
            'filiere' => 'Astrologie',
            'annee_academique' => '2026-2028',
        ]))
        ->assertSessionHasErrors(['telephone', 'tuteur_telephone', 'date_naissance', 'filiere', 'annee_academique']);

    expect($etudiant->fresh()->telephone)->toBe('0197000000');
});

it('refuse un e-mail déjà utilisé par un autre étudiant, quelle que soit la casse', function () {
    $etudiant = etudiantModif();
    autreEtudiantModif();

    $this->actingAs(utilisateurModif())
        ->put(route('etudiants.update', $etudiant), donneesModif(['email' => 'AWA@exemple.com']))
        ->assertSessionHasErrors('email');

    expect($etudiant->fresh()->email)->toBe('koffi@exemple.com');
});

it('refuse de prendre l\'identité d\'un autre étudiant', function () {
    $etudiant = etudiantModif();
    autreEtudiantModif();

    $this->actingAs(utilisateurModif())
        ->put(route('etudiants.update', $etudiant), donneesModif([
            'nom' => 'DOSSOU',
            'prenom' => 'awa',
            'date_naissance' => '2004-01-01',
        ]))
        ->assertSessionHasErrors('nom');

    expect($etudiant->fresh()->nom)->toBe('Adjovi');
});

it('accepte de conserver son propre e-mail et sa propre identité', function () {
    $etudiant = etudiantModif();

    $this->actingAs(utilisateurModif())
        ->put(route('etudiants.update', $etudiant), donneesModif(['lieu_naissance' => 'Porto-Novo']))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('etudiants.show', $etudiant));

    expect($etudiant->fresh()->lieu_naissance)->toBe('Porto-Novo');
});
