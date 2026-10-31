<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['name', 'identifiant', 'email', 'password', 'role', 'actif'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, LogsActivity, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => Role::class,
            'actif' => 'boolean',
            'locked_until' => 'datetime',
            'failed_login_attempts' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('comptes')
            ->logFillable()
            ->logExcept(['password'])
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['updated_at', 'failed_login_attempts', 'locked_until', 'remember_token']);
    }

    public function etudiantsModifies(): HasMany
    {
        return $this->hasMany(Etudiant::class, 'modifie_par_id');
    }

    public function isAdministrateur(): bool
    {
        return $this->role === Role::Administrateur;
    }

    public function isSecretariat(): bool
    {
        return $this->role === Role::Secretariat;
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function roleLabel(): string
    {
        return $this->role->label();
    }
}
