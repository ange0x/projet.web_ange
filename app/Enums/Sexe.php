<?php

namespace App\Enums;

enum Sexe: string
{
    case Masculin = 'masculin';
    case Feminin = 'feminin';

    public function label(): string
    {
        return match ($this) {
            self::Masculin => 'Masculin',
            self::Feminin => 'Féminin',
        };
    }
}
