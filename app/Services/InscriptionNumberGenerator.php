<?php

namespace App\Services;

use App\Models\Etudiant;

class InscriptionNumberGenerator
{
    public function generate(?int $year = null): string
    {
        $year ??= (int) now()->year;
        $prefix = $year.'-';

        // Les étudiants archivés comptent : un numéro n'est jamais réattribué.
        // On trie d'abord par longueur, pour que 2026-10000 passe après 2026-9999.
        $last = Etudiant::withTrashed()
            ->where('numero_inscription', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByRaw('LENGTH(numero_inscription) DESC')
            ->orderByDesc('numero_inscription')
            ->value('numero_inscription');

        $sequence = $last ? ((int) substr((string) $last, 5)) + 1 : 1;

        return sprintf('%s%04d', $prefix, $sequence);
    }
}
