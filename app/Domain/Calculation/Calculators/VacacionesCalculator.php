<?php

namespace App\Domain\Calculation\Calculators;

use App\Domain\Calculation\Contracts\ConceptCalculator;
use App\Domain\Calculation\Dto\Computation;
use App\Domain\Calculation\Dto\ComputedLines;
use App\Domain\Calculation\Dto\LiquidationContext;
use App\Domain\ValueObjects\ConceptDefinition;

final class VacacionesCalculator implements ConceptCalculator
{
    public function key(): string
    {
        return 'vacation';
    }

    public function computes(LiquidationContext $context, ComputedLines $computed, ConceptDefinition $definition): bool
    {
        return $context->days->vacaciones > 0;
    }

    public function compute(LiquidationContext $context, ComputedLines $computed, ConceptDefinition $definition): Computation
    {
        $base = $context->bases->vacacional;
        $days = $context->days->vacaciones;
        $divisor = $definition->defaultDivisor ?? $context->parameters->days('vacation_divisor');

        return new Computation(
            $base,
            (string) $days,
            $divisor,
            sprintf('(Base Vacacional × Días) / %d', $divisor),
            $base->times($days)->dividedBy($divisor)->rounded(),
        );
    }
}
