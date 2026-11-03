<?php

namespace App\Domain\Enum;

enum ContractStatus: string
{
    case Activo = 'activo';
    case Terminado = 'terminado';
    case EnLiquidacion = 'en_liquidacion';
    case Liquidado = 'liquidado';

    public function label(): string
    {
        return match ($this) {
            self::Activo => 'Activo',
            self::Terminado => 'Terminado',
            self::EnLiquidacion => 'En liquidación',
            self::Liquidado => 'Liquidado',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return list<self> */
    public static function assignableCases(): array
    {
        return [self::Activo, self::Terminado, self::EnLiquidacion];
    }

    public function isClosed(): bool
    {
        return $this !== self::Activo;
    }

    public function canBeLiquidated(): bool
    {
        return in_array($this, [self::Terminado, self::EnLiquidacion], true);
    }
}
