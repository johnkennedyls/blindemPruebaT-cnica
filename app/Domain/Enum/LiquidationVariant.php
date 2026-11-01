<?php

namespace App\Domain\Enum;

enum LiquidationVariant: string
{
    case Prestacional = 'prestacional';
    case ConIndemnizacion = 'con_indemnizacion';

    public function label(): string
    {
        return match ($this) {
            self::Prestacional => 'Liquidación prestacional',
            self::ConIndemnizacion => 'Liquidación con indemnización',
        };
    }

    public function includesIndemnization(): bool
    {
        return $this === self::ConIndemnizacion;
    }
}
