<?php

namespace App\Domain\Enum;

enum Gender: string
{
    case Masculino = 'm';
    case Femenino = 'f';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Masculino => 'Masculino',
            self::Femenino => 'Femenino',
            self::Otro => 'Otro',
        };
    }
}
