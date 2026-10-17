<?php

namespace App\Support;

use Illuminate\Support\Str;

final class TextNormalizer
{
    public static function normalize(?string $value): string
    {
        return mb_strtolower(Str::ascii(trim((string) $value)));
    }
}
