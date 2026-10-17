<?php

namespace App\Support;

use Carbon\CarbonInterface;

final class AcademicYear
{
    public static function current(?CarbonInterface $date = null): string
    {
        $date ??= now();
        $startYear = $date->month >= 9 ? $date->year : $date->year - 1;

        return $startYear.'-'.($startYear + 1);
    }

    public static function isValid(string $value): bool
    {
        if (! preg_match('/^(\d{4})-(\d{4})$/', $value, $matches)) {
            return false;
        }

        return ((int) $matches[2]) === ((int) $matches[1] + 1);
    }
}
