<?php

use App\Enums\Role;
use App\Models\Etudiant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function utilisateurRecherche(): User
{
    return User::firstOrCreate(
        ['identifiant' => 'recherche-test'],
        [
            'name' => 'Utilisateur Recherche',
            'password' => 'MotDePasse-2026',
            'role' => Role::Secretariat,
            'actif' => true,
        ],
    );
}

function etudiantRecherche(array $attributs = []): Etudiant
{
    return Etudiant::factory()->create(array_merge([
        'nom' => 'Defaut',
        'prenom' => 'Prenom',
        'filiere' => 'Gestion',
        'niveau' => 'Licence 1',
    ], $attributs));
}

function chercher($test, array $parametres = [])
{
    return $test->actingAs(utilisateurRecherche())->get(route('etudiants.index', $parametres));
}

// Accès

it('renvoie un visiteur non connecté vers la page de connexion', function () {
    $this->get(route('etudiants.index', ['terme' => 'Adjovi']))->assertRedirect(route('connexion'));
});

// US11 : rechercher par nom, prénom ou numéro

it('retrouve un étudiant par son nom', function () {
    etudiantRecherche(['nom' => 'Adjovi']);
    etudiantRecherche(['nom' => 'Dossou']);

    chercher($this, ['terme' => 'Adjovi'])
        ->assertOk()
        ->assertSee('Adjovi')
        ->assertDontSee('Dossou');
});

it('retrouve un étudiant par son prénom', function () {
    etudiantRecherche(['prenom' => 'Koffi']);
    etudiantRecherche(['prenom' => 'Awa']);

    chercher($this, ['terme' => 'Koffi'])
        ->assertSee('Koffi')
        ->assertDontSee('Awa');
});

it('retrouve un étudiant par son numéro d\'inscription', function () {
    etudiantRecherche(['numero_inscription' => '2026-0042', 'nom' => 'Adjovi']);
    etudiantRecherche(['numero_inscription' => '2026-0099', 'nom' => 'Dossou']);

    chercher($this, ['terme' => '2026-0042'])
        ->assertSee('Adjovi')
        ->assertDontSee('Dossou');
});

it('accepte une recherche partielle', function () {
    etudiantRecherche(['nom' => 'Adjovi']);
    etudiantRecherche(['nom' => 'Dossou']);

    chercher($this, ['terme' => 'adjo'])
        ->assertSee('Adjovi')
        ->assertDontSee('Dossou');

    chercher($this, ['terme' => '0042'])->assertOk();
});

it('ignore les majuscules et les accents', function () {
    etudiantRecherche(['prenom' => 'Élodie', 'nom' => 'Hounkpè']);
    etudiantRecherche(['prenom' => 'Awa', 'nom' => 'Dossou']);

    foreach (['elodie', 'ÉLODIE', 'hounkpe', 'HOUNKPÈ'] as $terme) {
        chercher($this, ['terme' => $terme])
            ->assertSee('Hounkpè')
            ->assertDontSee('Dossou');
    }
});

it('retrouve un étudiant en tapant son nom et son prénom, dans n\'importe quel ordre', function () {
    etudiantRecherche(['nom' => 'Adjovi', 'prenom' => 'Koffi']);
    etudiantRecherche(['nom' => 'Adjovi', 'prenom' => 'Awa']);
    etudiantRecherche(['nom' => 'Dossou', 'prenom' => 'Koffi']);

    foreach (['Adjovi Koffi', 'Koffi Adjovi', 'adjovi ko'] as $terme) {
        chercher($this, ['terme' => $terme])
            ->assertSee('Adjovi')
            ->assertSee('Koffi')
            ->assertSee('1 étudiant trouvé')
            ->assertDontSee('Dossou')
            ->assertDontSee('Awa');
    }
});

it('ignore les espaces autour du terme', function () {
    etudiantRecherche(['nom' => 'Adjovi']);
    etudiantRecherche(['nom' => 'Dossou']);

    chercher($this, ['terme' => '   Adjovi   '])
        ->assertSee('Adjovi')
        ->assertDontSee('Dossou');
});

it('affiche toute la liste quand le terme est vide', function () {
    etudiantRecherche(['nom' => 'Adjovi']);
    etudiantRecherche(['nom' => 'Dossou']);

    chercher($this, ['terme' => '   '])
        ->assertSee('Adjovi')
        ->assertSee('Dossou');
});

it('traite % et _ comme du texte et pas comme des jokers', function () {
    etudiantRecherche(['nom' => 'Adjovi']);
    etudiantRecherche(['nom' => 'Dossou']);

    foreach (['%', '_', 'A%i'] as $terme) {
        chercher($this, ['terme' => $terme])
            ->assertOk()
            ->assertSee('Aucun étudiant ne correspond à votre recherche')
            ->assertDontSee('Adjovi')
            ->assertDontSee('Dossou');
    }
});

it('accepte un terme très long sans erreur', function () {
    etudiantRecherche(['nom' => 'Adjovi']);

    chercher($this, ['terme' => str_repeat('a', 300)])->assertOk();
});

it('résiste aux paramètres falsifiés', function () {
    etudiantRecherche(['nom' => 'Adjovi']);

    chercher($this, ['terme' => ['x'], 'filiere' => ['x'], 'niveau' => ['x'], 'tri' => ['x'], 'ordre' => ['x']])
        ->assertOk();

    chercher($this, ['terme' => "' OR 1=1 --"])
        ->assertOk()
        ->assertSee('Aucun étudiant ne correspond');

    expect(Etudiant::count())->toBe(1);
});

// Résultats

it('annonce le nombre de résultats', function () {
    etudiantRecherche(['nom' => 'Adjovi', 'prenom' => 'Koffi']);
    etudiantRecherche(['nom' => 'Adjovi', 'prenom' => 'Awa']);
    etudiantRecherche(['nom' => 'Dossou']);

    chercher($this, ['terme' => 'Adjovi'])
        ->assertSee('role="status"', false)
        ->assertSee('2 étudiants trouvés');
});

it('affiche un message clair et un retour à la liste complète quand rien ne correspond', function () {
    etudiantRecherche(['nom' => 'Adjovi']);

    chercher($this, ['terme' => 'Zzzz'])
        ->assertOk()
        ->assertSee('Aucun étudiant ne correspond à votre recherche')
        ->assertSee('Afficher tous les étudiants')
        ->assertSee('role="status"', false)
        ->assertDontSee('Aucun étudiant enregistré');
});

it('propose le retour à la liste complète seulement quand une recherche est active', function () {
    etudiantRecherche(['nom' => 'Adjovi']);

    chercher($this)->assertDontSee('Afficher tous les étudiants');
    chercher($this, ['terme' => 'Adjovi'])->assertSee('Afficher tous les étudiants');
    chercher($this, ['niveau' => 'Licence 1'])->assertSee('Afficher tous les étudiants');
});

it('garde le terme saisi dans le champ de recherche', function () {
    etudiantRecherche(['nom' => 'Adjovi']);

    chercher($this, ['terme' => 'Adjovi'])->assertSee('value="Adjovi"', false);
});

// US12 : filtres

it('affiche des listes de filières et de niveaux issues de la configuration', function () {
    chercher($this)
        ->assertSee('Toutes les filières')
        ->assertSee('Tous les niveaux')
        ->assertSee('Comptabilité')
        ->assertSee('Informatique de gestion')
        ->assertSee('Licence 3')
        ->assertSee('Master 2');
});

it('filtre par filière', function () {
    etudiantRecherche(['nom' => 'Alpha', 'filiere' => 'Gestion']);
    etudiantRecherche(['nom' => 'Bravo', 'filiere' => 'Marketing']);

    chercher($this, ['filiere' => 'Marketing'])
        ->assertSee('Bravo')
        ->assertDontSee('Alpha')
        ->assertSee('1 étudiant trouvé');
});

it('filtre par niveau', function () {
    etudiantRecherche(['nom' => 'Alpha', 'niveau' => 'Licence 1']);
    etudiantRecherche(['nom' => 'Bravo', 'niveau' => 'Master 2']);

    chercher($this, ['niveau' => 'Master 2'])
        ->assertSee('Bravo')
        ->assertDontSee('Alpha');
});

it('combine la recherche, la filière et le niveau', function () {
    etudiantRecherche(['nom' => 'Adjovi', 'filiere' => 'Gestion', 'niveau' => 'Licence 1']);
    etudiantRecherche(['nom' => 'Adjovi', 'prenom' => 'Awa', 'filiere' => 'Marketing', 'niveau' => 'Licence 1']);
    etudiantRecherche(['nom' => 'Adjovi', 'prenom' => 'Eli', 'filiere' => 'Gestion', 'niveau' => 'Master 2']);
    etudiantRecherche(['nom' => 'Dossou', 'filiere' => 'Gestion', 'niveau' => 'Licence 1']);

    chercher($this, ['terme' => 'Adjovi', 'filiere' => 'Gestion', 'niveau' => 'Licence 1'])
        ->assertSee('1 étudiant trouvé')
        ->assertDontSee('Awa')
        ->assertDontSee('Eli')
        ->assertDontSee('Dossou');
});

it('ignore une filière ou un niveau qui n\'existe pas', function () {
    etudiantRecherche(['nom' => 'Alpha']);
    etudiantRecherche(['nom' => 'Bravo']);

    chercher($this, ['filiere' => 'Astrologie', 'niveau' => 'Doctorat 9'])
        ->assertOk()
        ->assertSee('Alpha')
        ->assertSee('Bravo');
});

it('garde les choix de filtres sélectionnés', function () {
    chercher($this, ['filiere' => 'Marketing', 'niveau' => 'Master 1'])
        ->assertSee('value="Marketing" selected', false)
        ->assertSee('value="Master 1" selected', false);
});

// Conservation entre recherche, tri et pagination

it('conserve les filtres en passant d\'une page à l\'autre', function () {
    foreach (range(1, 17) as $i) {
        etudiantRecherche(['nom' => sprintf('Gest%02d', $i), 'filiere' => 'Gestion']);
    }
    etudiantRecherche(['nom' => 'Mark01', 'filiere' => 'Marketing']);
    etudiantRecherche(['nom' => 'Mark02', 'filiere' => 'Marketing']);

    chercher($this, ['filiere' => 'Gestion'])
        ->assertSee('17 étudiants trouvés')
        ->assertSee('Gest15')
        ->assertDontSee('Gest16')
        ->assertDontSee('Mark01')
        ->assertSee('filiere=Gestion', false)
        ->assertSee('page=2', false);

    chercher($this, ['filiere' => 'Gestion', 'page' => 2])
        ->assertSee('Gest16')
        ->assertSee('Gest17')
        ->assertDontSee('Mark01')
        ->assertDontSee('Gest15');
});

it('conserve la recherche et les filtres quand on change le tri', function () {
    etudiantRecherche(['nom' => 'Alpha']);

    chercher($this, ['terme' => 'Alpha', 'filiere' => 'Gestion', 'niveau' => 'Licence 1'])
        ->assertSee('terme=Alpha', false)
        ->assertSee('filiere=Gestion', false)
        ->assertSee('tri=date_inscription', false);
});

it('trie les résultats d\'une recherche', function () {
    etudiantRecherche(['nom' => 'Adjovi', 'prenom' => 'Alpha', 'date_inscription' => '2026-01-10']);
    etudiantRecherche(['nom' => 'Adjovi', 'prenom' => 'Bravo', 'date_inscription' => '2026-03-10']);

    chercher($this, ['terme' => 'Adjovi', 'tri' => 'date_inscription', 'ordre' => 'desc'])
        ->assertSeeInOrder(['Bravo', 'Alpha']);
});

// Sécurité (OWASP)

it('échappe le terme recherché dans la page', function () {
    etudiantRecherche(['nom' => 'Adjovi']);

    chercher($this, ['terme' => '<script>alert(1)</script>'])
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;', false);
});

// Accessibilité (WCAG 2.2 AA)

it('présente un formulaire de recherche étiqueté', function () {
    chercher($this)
        ->assertSee('role="search"', false)
        ->assertSee('type="search"', false)
        ->assertSee('Rechercher un étudiant')
        ->assertSee('Filière')
        ->assertSee('Niveau')
        ->assertSee('Rechercher</button>', false);
});
