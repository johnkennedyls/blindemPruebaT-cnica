<?php

namespace App\Domain\Enum;

enum ParameterUnit: string
{
    case Rate = 'rate';
    case Days = 'days';
    case Amount = 'amount';

    public function label(): string
    {
        return match ($this) {
            self::Rate => 'Tasa',
            self::Days => 'Días',
            self::Amount => 'Valor',
        };
    }
}
