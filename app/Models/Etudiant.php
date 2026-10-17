<?php

namespace App\Models;

use App\Enums\Sexe;
use App\Support\TextNormalizer;
use Database\Factories\EtudiantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'nom',
    'prenom',
    'sexe',
    'date_naissance',
    'lieu_naissance',
    'adresse',
    'telephone',
    'email',
    'filiere',
    'niveau',
    'annee_academique',
    'date_inscription',
    'tuteur_nom',
    'tuteur_telephone',
])]
class Etudiant extends Model
{
    /** @use HasFactory<EtudiantFactory> */
    use HasFactory, LogsActivity;

    protected function casts(): array
    {
        return [
            'sexe' => Sexe::class,
            'date_naissance' => 'date',
            'date_inscription' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('etudiants')
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['updated_at', 'nom_search', 'prenom_search', 'modifie_par_id']);
    }

    protected static function booted(): void
    {
        static::saving(function (Etudiant $etudiant): void {
            $etudiant->syncSearchFields();

            if (blank($etudiant->email)) {
                $etudiant->email = null;
            }

            if ($etudiant->exists && auth()->check()) {
                $etudiant->modifie_par_id = auth()->id();
            }
        });
    }

    public function modificateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modifie_par_id');
    }

    public function nomComplet(): string
    {
        return $this->nom.' '.$this->prenom;
    }

    public function syncSearchFields(): void
    {
        $this->nom_search = TextNormalizer::normalize($this->nom);
        $this->prenom_search = TextNormalizer::normalize($this->prenom);
    }

    /**
     * @param  Builder<Etudiant>  $query
     * @return Builder<Etudiant>
     */
    public function scopeFiltrer(Builder $query, ?string $terme, ?string $filiere, ?string $niveau): Builder
    {
        if (filled($terme)) {
            $normalise = TextNormalizer::normalize($terme);

            $query->where(function (Builder $inner) use ($terme, $normalise): void {
                $inner->where('numero_inscription', 'like', '%'.$terme.'%')
                    ->orWhere('nom_search', 'like', '%'.$normalise.'%')
                    ->orWhere('prenom_search', 'like', '%'.$normalise.'%');
            });
        }

        if (filled($filiere)) {
            $query->where('filiere', $filiere);
        }

        if (filled($niveau)) {
            $query->where('niveau', $niveau);
        }

        return $query;
    }
}
