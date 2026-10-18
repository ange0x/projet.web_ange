<?php

use App\Enums\Role;
use App\Models\Etudiant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function utilisateurListe(Role $role = Role::Secretariat): User
{
    return User::create([
        'name' => 'Utilisateur Liste',
        'identifiant' => 'liste-'.$role->value,
        'password' => 'MotDePasse-2026',
        'role' => $role,
        'actif' => true,
    ]);
}

function etudiantListe(array $attributs = []): Etudiant
{
    return Etudiant::factory()->create($attributs);
}

// Accès

it('renvoie un visiteur non connecté vers la page de connexion', function () {
    $etudiant = etudiantListe();

    $this->get(route('etudiants.index'))->assertRedirect(route('connexion'));
    $this->get(route('etudiants.show', $etudiant))->assertRedirect(route('connexion'));
});

it('permet au secrétariat et à l\'administrateur de consulter la liste', function () {
    $this->actingAs(utilisateurListe(Role::Secretariat))->get(route('etudiants.index'))->assertOk();
    $this->actingAs(utilisateurListe(Role::Administrateur))->get(route('etudiants.index'))->assertOk();
});

it('affiche un lien vers la liste dans l\'accueil', function () {
    $this->actingAs(utilisateurListe())
        ->get(route('accueil'))
        ->assertSee('Liste des étudiants');
});

// US8 : la liste

it('affiche un message quand aucun étudiant n\'est enregistré', function () {
    $this->actingAs(utilisateurListe())
        ->get(route('etudiants.index'))
        ->assertOk()
        ->assertSee('Aucun étudiant enregistré');
});

it('affiche les informations principales de chaque étudiant', function () {
    etudiantListe([
        'numero_inscription' => '2026-0042',
        'nom' => 'Adjovi',
        'prenom' => 'Koffi',
        'filiere' => 'Gestion',
        'niveau' => 'Licence 1',
        'date_inscription' => '2026-03-12',
    ]);

    $this->actingAs(utilisateurListe())
        ->get(route('etudiants.index'))
        ->assertOk()
        ->assertSee('2026-0042')
        ->assertSee('Adjovi')
        ->assertSee('Koffi')
        ->assertSee('Gestion')
        ->assertSee('Licence 1')
        ->assertSee('12/03/2026');
});

it('n\'affiche pas le téléphone ni l\'e-mail dans la liste', function () {
    etudiantListe(['telephone' => '0155555555', 'email' => 'secret@exemple.com']);

    $this->actingAs(utilisateurListe())
        ->get(route('etudiants.index'))
        ->assertDontSee('0155555555')
        ->assertDontSee('secret@exemple.com');
});

it('affiche le nombre total d\'étudiants et un tableau structuré', function () {
    etudiantListe();
    etudiantListe();
    etudiantListe();

    $this->actingAs(utilisateurListe())
        ->get(route('etudiants.index'))
        ->assertSee('Étudiants inscrits (3)')
        ->assertSee('<caption', false)
        ->assertSee('scope="col"', false)
        ->assertSee('scope="row"', false);
});

it('propose un lien vers l\'enregistrement d\'un étudiant', function () {
    $this->actingAs(utilisateurListe())
        ->get(route('etudiants.index'))
        ->assertSee('Enregistrer un étudiant')
        ->assertSee(route('etudiants.create'));
});

it('propose un lien vers la fiche de chaque étudiant', function () {
    $etudiant = etudiantListe();

    $this->actingAs(utilisateurListe())
        ->get(route('etudiants.index'))
        ->assertSee(route('etudiants.show', $etudiant));
});

// US9 : le tri

it('classe par défaut par nom, sans tenir compte des accents', function () {
    etudiantListe(['nom' => 'Zinsou', 'prenom' => 'Ama']);
    etudiantListe(['nom' => 'Émile', 'prenom' => 'Ama']);
    etudiantListe(['nom' => 'Dossou', 'prenom' => 'Ama']);

    $this->actingAs(utilisateurListe())
        ->get(route('etudiants.index'))
        ->assertSeeInOrder(['Dossou', 'Émile', 'Zinsou']);
});

it('classe par nom décroissant', function () {
    etudiantListe(['nom' => 'Alpha', 'prenom' => 'Ama']);
    etudiantListe(['nom' => 'Bravo', 'prenom' => 'Ama']);
    etudiantListe(['nom' => 'Charlie', 'prenom' => 'Ama']);

    $this->actingAs(utilisateurListe())
        ->get(route('etudiants.index', ['tri' => 'nom', 'ordre' => 'desc']))
        ->assertSeeInOrder(['Charlie', 'Bravo', 'Alpha']);
});

it('classe par date d\'inscription, du plus récent au plus ancien', function () {
    etudiantListe(['nom' => 'Alpha', 'prenom' => 'Ama', 'date_inscription' => '2026-01-10']);
    etudiantListe(['nom' => 'Bravo', 'prenom' => 'Ama', 'date_inscription' => '2026-02-10']);
    etudiantListe(['nom' => 'Charlie', 'prenom' => 'Ama', 'date_inscription' => '2026-03-10']);

    $this->actingAs(utilisateurListe())
        ->get(route('etudiants.index', ['tri' => 'date_inscription', 'ordre' => 'desc']))
        ->assertSeeInOrder(['Charlie', 'Bravo', 'Alpha']);
});

it('classe par date d\'inscription, du plus ancien au plus récent', function () {
    etudiantListe(['nom' => 'Alpha', 'prenom' => 'Ama', 'date_inscription' => '2026-03-10']);
    etudiantListe(['nom' => 'Bravo', 'prenom' => 'Ama', 'date_inscription' => '2026-02-10']);
    etudiantListe(['nom' => 'Charlie', 'prenom' => 'Ama', 'date_inscription' => '2026-01-10']);

    $this->actingAs(utilisateurListe())
        ->get(route('etudiants.index', ['tri' => 'date_inscription', 'ordre' => 'asc']))
        ->assertSeeInOrder(['Charlie', 'Bravo', 'Alpha']);
});

it('ignore un tri ou un ordre inconnu au lieu de provoquer une erreur', function () {
    etudiantListe(['nom' => 'Alpha', 'prenom' => 'Ama']);
    etudiantListe(['nom' => 'Bravo', 'prenom' => 'Ama']);

    $this->actingAs(utilisateurListe())
        ->get(route('etudiants.index', ['tri' => 'password', 'ordre' => 'sideways']))
        ->assertOk()
        ->assertSeeInOrder(['Alpha', 'Bravo']);
});

it('résiste à une injection SQL dans les paramètres de tri', function () {
    etudiantListe();

    $this->actingAs(utilisateurListe())
        ->get(route('etudiants.index', ['tri' => 'nom; DROP TABLE etudiants', 'ordre' => 'desc; --']))
        ->assertOk();

    expect(Etudiant::count())->toBe(1);
});

it('indique le tri actif de façon accessible', function () {
    etudiantListe();

    $this->actingAs(utilisateurListe())
        ->get(route('etudiants.index', ['tri' => 'date_inscription', 'ordre' => 'desc']))
        ->assertSee('aria-sort="descending"', false);
});

// Pagination

it('affiche 15 étudiants par page avec la navigation entre les pages', function () {
    foreach (range(1, 17) as $i) {
        etudiantListe(['nom' => sprintf('Nom%02d', $i), 'prenom' => 'Ama']);
    }

    $this->actingAs(utilisateurListe());

    $this->get(route('etudiants.index'))
        ->assertSee('Nom01')
        ->assertSee('Nom15')
        ->assertDontSee('Nom16')
        ->assertSee('Page 1 sur 2')
        ->assertSee('page=2', false);

    $this->get(route('etudiants.index', ['page' => 2]))
        ->assertSee('Nom16')
        ->assertSee('Nom17')
        ->assertDontSee('Nom15')
        ->assertSee('Page 2 sur 2');
});

it('conserve le tri en passant d\'une page à l\'autre', function () {
    foreach (range(1, 17) as $i) {
        etudiantListe(['nom' => sprintf('Nom%02d', $i), 'prenom' => 'Ama']);
    }

    $this->actingAs(utilisateurListe());

    $this->get(route('etudiants.index', ['tri' => 'nom', 'ordre' => 'desc']))
        ->assertSee('Nom17')
        ->assertDontSee('Nom01')
        ->assertSee('ordre=desc', false)
        ->assertSee('page=2', false);

    $this->get(route('etudiants.index', ['tri' => 'nom', 'ordre' => 'desc', 'page' => 2]))
        ->assertSee('Nom01')
        ->assertSee('Nom02')
        ->assertDontSee('Nom17');
});

// US10 : la fiche détaillée

it('affiche toutes les informations de l\'étudiant dans la fiche', function () {
    $etudiant = etudiantListe([
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
    ]);

    $this->actingAs(utilisateurListe())
        ->get(route('etudiants.show', $etudiant))
        ->assertOk()
        ->assertSee('Adjovi')
        ->assertSee('Koffi')
        ->assertSee('2026-0042')
        ->assertSee('Masculin')
        ->assertSee('12/03/2005')
        ->assertSee('Cotonou')
        ->assertSee('Quartier Akpakpa')
        ->assertSee('0197000000')
        ->assertSee('koffi@exemple.com')
        ->assertSee('Gestion')
        ->assertSee('Licence 1')
        ->assertSee('2026-2027')
        ->assertSee('14/09/2026')
        ->assertSee('Mme Adjovi')
        ->assertSee('0197000001');
});

it('étiquette chaque information de la fiche', function () {
    $etudiant = etudiantListe();

    $this->actingAs(utilisateurListe())
        ->get(route('etudiants.show', $etudiant))
        ->assertSee('Numéro d’inscription')
        ->assertSee('Date de naissance')
        ->assertSee('Lieu de naissance')
        ->assertSee('Téléphone')
        ->assertSee('E-mail')
        ->assertSee('Filière')
        ->assertSee('Niveau')
        ->assertSee('Année académique')
        ->assertSee('Date d’inscription')
        ->assertSee('Nom du tuteur')
        ->assertSee('Téléphone du tuteur')
        ->assertSee('<dt>', false)
        ->assertSee('<dd>', false);
});

it('affiche « Non renseigné » pour les informations facultatives vides', function () {
    $etudiant = etudiantListe([
        'lieu_naissance' => null,
        'adresse' => null,
        'email' => null,
        'tuteur_nom' => null,
        'tuteur_telephone' => null,
    ]);

    $this->actingAs(utilisateurListe())
        ->get(route('etudiants.show', $etudiant))
        ->assertSee('Non renseigné');
});

it('affiche la dernière modification avec son auteur', function () {
    $auteur = utilisateurListe();
    $etudiant = etudiantListe(['modifie_par_id' => $auteur->id]);

    $this->actingAs($auteur)
        ->get(route('etudiants.show', $etudiant))
        ->assertSee('Dernière modification')
        ->assertSee('Utilisateur Liste');
});

it('propose le retour à la liste', function () {
    $etudiant = etudiantListe();

    $this->actingAs(utilisateurListe())
        ->get(route('etudiants.show', $etudiant))
        ->assertSee('Retour à la liste')
        ->assertSee(route('etudiants.index'));
});

it('répond 404 pour un étudiant qui n\'existe pas', function () {
    $this->actingAs(utilisateurListe())
        ->get(route('etudiants.show', 99999))
        ->assertNotFound();
});

// Sécurité (OWASP)

it('échappe les textes affichés dans la liste et dans la fiche', function () {
    $etudiant = etudiantListe(['nom' => '<script>alert(1)</script>', 'prenom' => 'Ama']);

    $this->actingAs(utilisateurListe());

    $this->get(route('etudiants.index'))
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;', false);

    $this->get(route('etudiants.show', $etudiant))
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;', false);
});
