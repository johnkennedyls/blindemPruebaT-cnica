<?php

namespace App\Domain\Enum;

enum TransportAuxiliaryMode: string
{
    case Auto = 'auto';
    case Manual = 'manual';
    case SinDerecho = 'sin_derecho';

    public function label(): string
    {
        return match ($this) {
            self::Auto => 'Automático (SMLMV)',
            self::Manual => 'Valor manual',
            self::SinDerecho => 'Sin derecho',
        };
    }

    public function accruesAuxiliary(): bool
    {
        return $this !== self::SinDerecho;
    }
}
