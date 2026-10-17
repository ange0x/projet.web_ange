<?php

namespace App\Enums;

enum Role: string
{
    case Administrateur = 'administrateur';
    case Secretariat = 'secretariat';

    public function label(): string
    {
        return match ($this) {
            self::Administrateur => 'Administrateur',
            self::Secretariat => 'Secrétariat',
        };
    }
}
