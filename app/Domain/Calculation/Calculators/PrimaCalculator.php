<?php

namespace App\Domain\Calculation\Calculators;

use App\Domain\Calculation\Contracts\ConceptCalculator;
use App\Domain\Calculation\Dto\Computation;
use App\Domain\Calculation\Dto\ComputedLines;
use App\Domain\Calculation\Dto\LiquidationContext;
use App\Domain\ValueObjects\ConceptDefinition;

final class PrimaCalculator implements ConceptCalculator
{
    public function key(): string
    {
        return 'prima';
    }

    public function computes(LiquidationContext $context, ComputedLines $computed, ConceptDefinition $definition): bool
    {
        return $context->days->prima > 0;
    }

    public function compute(LiquidationContext $context, ComputedLines $computed, ConceptDefinition $definition): Computation
    {
        $base = $context->bases->prestacional;
        $days = $context->days->prima;
        $divisor = $definition->defaultDivisor ?? $context->parameters->days('prima_divisor');

        return new Computation(
            $base,
            (string) $days,
            $divisor,
            sprintf('(Base Prestacional × Días) / %d', $divisor),
            $base->times($days)->dividedBy($divisor)->rounded(),
        );
    }
}
