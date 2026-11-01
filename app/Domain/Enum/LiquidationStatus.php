<?php

namespace App\Domain\Enum;

enum LiquidationStatus: string
{
    case Borrador = 'borrador';
    case Calculada = 'calculada';
    case Aprobada = 'aprobada';
    case Anulada = 'anulada';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Calculada => 'Calculada',
            self::Aprobada => 'Aprobada',
            self::Anulada => 'Anulada',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Borrador;
    }

    public function isExportable(): bool
    {
        return in_array($this, [self::Calculada, self::Aprobada], true);
    }
}
