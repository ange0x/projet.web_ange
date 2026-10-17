<?php

namespace Database\Factories;

use App\Enums\Sexe;
use App\Models\Etudiant;
use App\Support\AcademicYear;
use App\Support\TextNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Etudiant>
 */
class EtudiantFactory extends Factory
{
    public function definition(): array
    {
        $nom = fake()->lastName();
        $prenom = fake()->firstName();
        $filieres = config('ecole.filieres');
        $niveaux = config('ecole.niveaux');

        return [
            'numero_inscription' => fake()->unique()->numerify('2026-####'),
            'nom' => $nom,
            'prenom' => $prenom,
            'nom_search' => TextNormalizer::normalize($nom),
            'prenom_search' => TextNormalizer::normalize($prenom),
            'sexe' => fake()->randomElement(Sexe::cases()),
            'date_naissance' => fake()->dateTimeBetween('-30 years', '-16 years')->format('Y-m-d'),
            'lieu_naissance' => fake()->city(),
            'adresse' => fake()->optional()->streetAddress(),
            'telephone' => '01'.fake()->numerify('########'),
            'email' => fake()->optional()->unique()->safeEmail(),
            'filiere' => fake()->randomElement($filieres),
            'niveau' => fake()->randomElement($niveaux),
            'annee_academique' => AcademicYear::current(),
            'date_inscription' => now()->toDateString(),
            'tuteur_nom' => fake()->optional()->name(),
            'tuteur_telephone' => fake()->optional()->numerify('01########'),
        ];
    }
}
