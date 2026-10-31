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
use Illuminate\Database\Eloquent\SoftDeletes;
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
    use HasFactory, LogsActivity, SoftDeletes;

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

    /**
     * Le compte de l'auteur peut être archivé : son nom reste affiché sur la fiche.
     */
    public function modificateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modifie_par_id')->withTrashed();
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
     * Recherche par mots : chaque mot saisi doit se retrouver dans le nom,
     * le prénom ou le numéro d'inscription, dans n'importe quel ordre.
     *
     * @param  Builder<Etudiant>  $query
     * @return Builder<Etudiant>
     */
    public function scopeFiltrer(Builder $query, ?string $terme, ?string $filiere, ?string $niveau): Builder
    {
        foreach (self::motsDeRecherche($terme) as $mot) {
            $motif = '%'.self::echapperJokers($mot).'%';
            $motifNormalise = '%'.self::echapperJokers(TextNormalizer::normalize($mot)).'%';

            $query->where(function (Builder $inner) use ($motif, $motifNormalise): void {
                $inner->whereRaw("numero_inscription LIKE ? ESCAPE '!'", [$motif])
                    ->orWhereRaw("nom_search LIKE ? ESCAPE '!'", [$motifNormalise])
                    ->orWhereRaw("prenom_search LIKE ? ESCAPE '!'", [$motifNormalise]);
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

    /**
     * Découpe le terme en mots (100 caractères et 5 mots au maximum).
     *
     * @return array<int, string>
     */
    public static function motsDeRecherche(?string $terme): array
    {
        $terme = mb_substr(trim((string) $terme), 0, 100);

        if ($terme === '') {
            return [];
        }

        return array_slice(preg_split('/\s+/u', $terme, -1, PREG_SPLIT_NO_EMPTY), 0, 5);
    }

    /**
     * Les caractères % et _ sont cherchés comme du texte, pas comme des jokers.
     * Le caractère d'échappement est « ! », déclaré par ESCAPE dans la requête.
     */
    private static function echapperJokers(string $valeur): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $valeur);
    }
}
