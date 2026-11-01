<?php

namespace App\Domain\Enum;

enum HolderType: string
{
    case Natural = 'natural';
    case Juridica = 'juridica';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Natural => 'Persona natural',
            self::Juridica => 'Persona jurídica',
            self::Both => 'Ambas',
        };
    }
}
