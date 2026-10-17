<?php

namespace App\Policies;

use App\Models\Etudiant;
use App\Models\User;

class EtudiantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->actif;
    }

    public function view(User $user, Etudiant $etudiant): bool
    {
        return $user->actif;
    }

    public function create(User $user): bool
    {
        return $user->actif;
    }

    public function update(User $user, Etudiant $etudiant): bool
    {
        return $user->actif;
    }
}
