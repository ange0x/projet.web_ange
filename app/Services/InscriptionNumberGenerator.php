<?php

namespace App\Services;

use App\Models\Etudiant;

class InscriptionNumberGenerator
{
    public function generate(?int $year = null): string
    {
        $year ??= (int) now()->year;
        $prefix = $year.'-';

        $last = Etudiant::query()
            ->where('numero_inscription', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('numero_inscription')
            ->value('numero_inscription');

        $sequence = $last ? ((int) substr((string) $last, 5)) + 1 : 1;

        return sprintf('%s%04d', $prefix, $sequence);
    }
}
