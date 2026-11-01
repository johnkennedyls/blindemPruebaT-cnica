<?php

namespace App\Domain\Enum;

enum BaseKind: string
{
    case Prestacional = 'prestacional';
    case Vacacional = 'vacacional';
    case SalarioPendiente = 'salario_pendiente';
    case Otro = 'otro';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Prestacional => 'Base prestacional',
            self::Vacacional => 'Base vacacional',
            self::SalarioPendiente => 'Salario pendiente',
            self::Otro => 'Otra base',
            self::Manual => 'Valor digitado',
        };
    }
}
