<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrateur();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdministrateur();
    }

    public function create(User $user): bool
    {
        return $user->isAdministrateur();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdministrateur();
    }

    public function unlock(User $user, User $model): bool
    {
        return $user->isAdministrateur();
    }
}
