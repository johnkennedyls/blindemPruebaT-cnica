<?php

namespace App\Domain\Enum;

enum ContractModality: string
{
    case Indefinido = 'indefinido';
    case TerminoFijo = 'termino_fijo';
    case ObraOLabor = 'obra_o_labor';
    case Aprendizaje = 'aprendizaje';
    case Ocasional = 'ocasional';
    case Servicios = 'servicios';

    public function label(): string
    {
        return match ($this) {
            self::Indefinido => 'Término indefinido',
            self::TerminoFijo => 'Término fijo',
            self::ObraOLabor => 'Obra o labor',
            self::Aprendizaje => 'Aprendizaje',
            self::Ocasional => 'Ocasional',
            self::Servicios => 'Prestación de servicios',
        };
    }

    public function countsAsIndefinidoForIndemnization(): bool
    {
        return in_array($this, [self::Indefinido, self::ObraOLabor], true);
    }
}
