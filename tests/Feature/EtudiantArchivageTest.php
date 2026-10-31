<?php

use App\Enums\Role;
use App\Models\Etudiant;
use App\Models\User;
use App\Services\InscriptionNumberGenerator;
use App\Support\AcademicYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function acteurEtudiantArchive(Role $role = Role::Secretariat): User
{
    return User::firstOrCreate(
        ['identifiant' => 'archive-etu-'.$role->value],
        [
            'name' => 'Acteur '.$role->value,
            'password' => 'MotDePasse-2026',
            'role' => $role,
            'actif' => true,
        ],
    );
}

function etudiantAArchiver(array $attributs = []): Etudiant
{
    return Etudiant::factory()->create(array_merge([
        'nom' => 'Adjovi',
        'prenom' => 'Koffi',
    ], $attributs));
}

function donneesArchive(array $surcharge = []): array
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

function journalEtudiantArchive(string $evenement, int $auteur, int $cible): int
{
    return DB::table('activity_log')
        ->where('log_name', 'etudiants')
        ->where('event', $evenement)
        ->where('causer_id', $auteur)
        ->where('subject_id', $cible)
        ->count();
}

// Accès

it('renvoie un visiteur vers la connexion et ne change rien', function () {
    $etudiant = etudiantAArchiver();

    $this->get(route('etudiants.archiver.confirmer', $etudiant))->assertRedirect(route('connexion'));
    $this->post(route('etudiants.archiver', $etudiant))->assertRedirect(route('connexion'));
    $this->get(route('etudiants.archives'))->assertRedirect(route('connexion'));
    $this->post(route('etudiants.restaurer', $etudiant))->assertRedirect(route('connexion'));

    expect($etudiant->fresh()->trashed())->toBeFalse();
});

it('permet au secrétariat et à l\'administrateur d\'archiver un étudiant', function () {
    $a = etudiantAArchiver(['nom' => 'Alpha']);
    $b = etudiantAArchiver(['nom' => 'Bravo']);

    $this->actingAs(acteurEtudiantArchive(Role::Secretariat))
        ->post(route('etudiants.archiver', $a))
        ->assertRedirect(route('etudiants.index'));

    $this->actingAs(acteurEtudiantArchive(Role::Administrateur))
        ->post(route('etudiants.archiver', $b))
        ->assertRedirect(route('etudiants.index'));

    expect(Etudiant::count())->toBe(0)
        ->and(Etudiant::onlyTrashed()->count())->toBe(2);
});

// R27 : confirmation puis archivage

it('affiche une page de confirmation sans archiver', function () {
    $etudiant = etudiantAArchiver(['numero_inscription' => '2026-0042']);

    $this->actingAs(acteurEtudiantArchive())
        ->get(route('etudiants.archiver.confirmer', $etudiant))
        ->assertOk()
        ->assertSee('Archiver la fiche')
        ->assertSee('Adjovi')
        ->assertSee('2026-0042')
        ->assertSee('Annuler')
        ->assertSee(route('etudiants.archiver', $etudiant));

    expect($etudiant->fresh()->trashed())->toBeFalse();
});

it('archive un étudiant après confirmation et affiche un message dans la liste', function () {
    $etudiant = etudiantAArchiver();

    $this->actingAs(acteurEtudiantArchive())
        ->post(route('etudiants.archiver', $etudiant))
        ->assertRedirect(route('etudiants.index'))
        ->assertSessionHas('statut');

    expect(Etudiant::find($etudiant->id))->toBeNull()
        ->and(Etudiant::withTrashed()->whereKey($etudiant->id)->exists())->toBeTrue();

    $this->get(route('etudiants.index'))
        ->assertSee('role="status"', false)
        ->assertSee('a été archivée');
});

// R28 : effets de l'archivage

it('retire l\'étudiant archivé de la liste', function () {
    $archive = etudiantAArchiver(['nom' => 'Zorglub']);
    $reste = etudiantAArchiver(['nom' => 'Quimby']);
    $archive->delete();

    $this->actingAs(acteurEtudiantArchive())
        ->get(route('etudiants.index'))
        ->assertDontSee(route('etudiants.show', $archive))
        ->assertSee(route('etudiants.show', $reste));
});

it('répond 404 pour la fiche, la modification et la confirmation d\'un étudiant archivé', function () {
    $etudiant = etudiantAArchiver();
    $etudiant->delete();

    $this->actingAs(acteurEtudiantArchive());

    $this->get(route('etudiants.show', $etudiant))->assertNotFound();
    $this->get(route('etudiants.edit', $etudiant))->assertNotFound();
    $this->post(route('etudiants.confirmer', $etudiant), [])->assertNotFound();
    $this->put(route('etudiants.update', $etudiant), [])->assertNotFound();
});

it('retire l\'étudiant archivé des résultats de recherche', function () {
    $etudiant = etudiantAArchiver(['nom' => 'Adjovi']);
    $etudiant->delete();

    $this->actingAs(acteurEtudiantArchive())
        ->get(route('etudiants.index', ['terme' => 'Adjovi']))
        ->assertOk()
        ->assertSee('Aucun étudiant ne correspond à votre recherche');
});

it('ne compte pas les étudiants archivés dans le total', function () {
    etudiantAArchiver(['nom' => 'Alpha']);
    $archive = etudiantAArchiver(['nom' => 'Bravo']);
    $archive->delete();

    $this->actingAs(acteurEtudiantArchive())
        ->get(route('etudiants.index'))
        ->assertSee('Étudiants inscrits (1)');
});

it('ne réattribue jamais le numéro d\'un étudiant archivé', function () {
    $etudiant = etudiantAArchiver(['numero_inscription' => '2026-0001']);
    $etudiant->delete();

    expect(app(InscriptionNumberGenerator::class)->generate(2026))->toBe('2026-0002');
});

it('continue la numérotation au-delà de 9999', function () {
    etudiantAArchiver(['numero_inscription' => '2026-9999']);

    expect(app(InscriptionNumberGenerator::class)->generate(2026))->toBe('2026-10000');
});

it('garde l\'e-mail d\'un étudiant archivé réservé', function () {
    $archive = etudiantAArchiver(['email' => 'koffi@exemple.com']);
    $archive->delete();

    $this->actingAs(acteurEtudiantArchive())
        ->post(route('etudiants.store'), donneesArchive([
            'nom' => 'Dossou',
            'prenom' => 'Awa',
            'date_naissance' => '2004-01-01',
            'email' => 'koffi@exemple.com',
        ]))
        ->assertSessionHasErrors('email');
});

it('compte les étudiants archivés dans la détection des doublons d\'identité', function () {
    $archive = etudiantAArchiver([
        'nom' => 'Adjovi',
        'prenom' => 'Koffi',
        'date_naissance' => '2005-03-12',
        'email' => 'ancien@exemple.com',
    ]);
    $archive->delete();

    $this->actingAs(acteurEtudiantArchive())
        ->post(route('etudiants.store'), donneesArchive(['email' => 'nouveau@exemple.com']))
        ->assertSessionHasErrors('nom');

    expect(Etudiant::count())->toBe(0);
});

// R29 : archives et restauration

it('liste seulement les étudiants archivés dans les archives', function () {
    $actif = etudiantAArchiver(['nom' => 'Quimby']);
    $archive = etudiantAArchiver(['nom' => 'Zorglub']);
    $archive->delete();

    $this->actingAs(acteurEtudiantArchive(Role::Secretariat))
        ->get(route('etudiants.archives'))
        ->assertOk()
        ->assertSee('Zorglub')
        ->assertDontSee('Quimby')
        ->assertSee(route('etudiants.restaurer', $archive));
});

it('restaure un étudiant archivé', function () {
    $etudiant = etudiantAArchiver();
    $etudiant->delete();

    $this->actingAs(acteurEtudiantArchive())
        ->post(route('etudiants.restaurer', $etudiant))
        ->assertRedirect(route('etudiants.archives'))
        ->assertSessionHas('statut');

    expect(Etudiant::find($etudiant->id))->not->toBeNull();

    $this->get(route('etudiants.index'))->assertSee(route('etudiants.show', $etudiant));
});

it('prévient quand on restaure un étudiant qui n\'est pas archivé', function () {
    $etudiant = etudiantAArchiver();
    $acteur = acteurEtudiantArchive();

    $this->actingAs($acteur)
        ->post(route('etudiants.restaurer', $etudiant))
        ->assertRedirect(route('etudiants.index'))
        ->assertSessionHas('statut', fn ($message) => str_contains($message, 'pas archivée'));

    expect(journalEtudiantArchive('unarchived', $acteur->id, $etudiant->id))->toBe(0);
});

// R30 : journal

it('journalise l\'archivage et la restauration sans donnée de l\'étudiant', function () {
    $acteur = acteurEtudiantArchive(Role::Administrateur);
    $etudiant = etudiantAArchiver(['nom' => 'Adjovi', 'email' => 'secret-journal@exemple.com']);

    $this->actingAs($acteur)->post(route('etudiants.archiver', $etudiant));
    expect(journalEtudiantArchive('archived', $acteur->id, $etudiant->id))->toBe(1);

    $this->post(route('etudiants.restaurer', $etudiant));
    expect(journalEtudiantArchive('unarchived', $acteur->id, $etudiant->id))->toBe(1);

    $lignes = json_encode(DB::table('activity_log')->whereIn('event', ['archived', 'unarchived'])->get());

    expect($lignes)->not->toContain('Adjovi')
        ->and($lignes)->not->toContain('secret-journal@exemple.com');
});

// Garde-fous

it('ne propose aucune suppression définitive', function () {
    $etudiant = etudiantAArchiver();

    $this->actingAs(acteurEtudiantArchive())
        ->delete('/etudiants/'.$etudiant->id)
        ->assertStatus(405);

    expect(Etudiant::withTrashed()->whereKey($etudiant->id)->exists())->toBeTrue();
});

it('répond 404 quand on archive un étudiant déjà archivé ou inexistant', function () {
    $etudiant = etudiantAArchiver();
    $etudiant->delete();

    $this->actingAs(acteurEtudiantArchive());

    $this->post(route('etudiants.archiver', $etudiant))->assertNotFound();
    $this->get(route('etudiants.archiver.confirmer', $etudiant))->assertNotFound();
    $this->post('/etudiants/99999/archiver')->assertNotFound();
});

it('propose les liens d\'archivage dans la fiche et dans la liste', function () {
    $etudiant = etudiantAArchiver();

    $this->actingAs(acteurEtudiantArchive())
        ->get(route('etudiants.show', $etudiant))
        ->assertSee('Archiver la fiche')
        ->assertSee(route('etudiants.archiver.confirmer', $etudiant));

    $this->get(route('etudiants.index'))
        ->assertSee('Étudiants archivés')
        ->assertSee(route('etudiants.archives'));
});

it('affiche encore l\'auteur de la dernière modification quand son compte est archivé', function () {
    $auteur = User::create([
        'name' => 'Auteur Archivé',
        'identifiant' => 'auteur-archive',
        'password' => 'MotDePasse-2026',
        'role' => Role::Secretariat,
        'actif' => true,
    ]);
    $etudiant = etudiantAArchiver(['modifie_par_id' => $auteur->id]);
    $auteur->delete();

    $this->actingAs(acteurEtudiantArchive())
        ->get(route('etudiants.show', $etudiant))
        ->assertOk()
        ->assertSee('Auteur Archivé');
});
