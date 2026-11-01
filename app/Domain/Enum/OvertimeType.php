<?php

namespace App\Domain\Enum;

enum OvertimeType: string
{
    case ExtraDiurna = 'extra_diurna';
    case ExtraNocturna = 'extra_nocturna';
    case Dominical = 'dominical';
    case Recargo = 'recargo';

    public function label(): string
    {
        return match ($this) {
            self::ExtraDiurna => 'Horas extras diurnas',
            self::ExtraNocturna => 'Horas extras nocturnas',
            self::Dominical => 'Dominatical',
            self::Recargo => 'Recargos',
        };
    }
}
