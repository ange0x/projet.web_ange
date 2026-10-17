<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Etudiant;
use App\Models\User;
use App\Services\InscriptionNumberGenerator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::query()->create([
            'name' => 'Administrateur ESGC',
            'identifiant' => 'admin',
            'password' => 'Admin12345',
            'role' => Role::Administrateur,
            'actif' => true,
        ]);

        User::query()->create([
            'name' => 'Secrétariat ESGC',
            'identifiant' => 'secretariat',
            'password' => 'Secretariat12345',
            'role' => Role::Secretariat,
            'actif' => true,
        ]);

        $generator = app(InscriptionNumberGenerator::class);

        Etudiant::factory()
            ->count(12)
            ->make()
            ->each(function (Etudiant $etudiant) use ($generator): void {
                DB::transaction(function () use ($etudiant, $generator): void {
                    $etudiant->numero_inscription = $generator->generate();
                    $etudiant->save();
                });
            });
    }
}
