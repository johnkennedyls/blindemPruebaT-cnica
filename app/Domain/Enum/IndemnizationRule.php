<?php

namespace App\Domain\Enum;

enum IndemnizationRule: string
{
    case Art64SegunSalario = 'art64_segun_salario';
    case Art64LiteralA = 'art64_lt_a';
    case Art64LiteralB = 'art64_lt_b';
    case PlazoRestante = 'plazo_restante';
    case Ninguna = 'ninguna';

    public function label(): string
    {
        return match ($this) {
            self::Art64SegunSalario => 'Art. 64: literal según el salario',
            self::Art64LiteralA => 'Art. 64 lit. a (30 + 20 días)',
            self::Art64LiteralB => 'Art. 64 lit. b (20 + 15 días)',
            self::PlazoRestante => 'Salarios faltantes del plazo',
            self::Ninguna => 'Sin indemnización',
        };
    }

    public function applies(): bool
    {
        return $this !== self::Ninguna;
    }
}
