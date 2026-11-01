<?php

namespace App\Domain\Calculation\Calculators;

use App\Domain\Calculation\Contracts\ConceptCalculator;
use App\Domain\Calculation\Dto\Computation;
use App\Domain\Calculation\Dto\ComputedLines;
use App\Domain\Calculation\Dto\LiquidationContext;
use App\Domain\ValueObjects\ConceptDefinition;

final class AuxilioTransporteCalculator implements ConceptCalculator
{
    public function key(): string
    {
        return 'transport_auxiliary';
    }

    public function computes(LiquidationContext $context, ComputedLines $computed, ConceptDefinition $definition): bool
    {
        return $context->days->pendingSalary > 0 && ! $context->bases->transport->isZero();
    }

    public function compute(LiquidationContext $context, ComputedLines $computed, ConceptDefinition $definition): Computation
    {
        $base = $context->bases->transport;
        $days = $context->days->pendingSalary;
        $divisor = $definition->defaultDivisor ?? $context->parameters->days('commercial_month_days');

        return new Computation(
            $base,
            (string) $days,
            $divisor,
            sprintf('(Auxilio Mensual × Días) / %d', $divisor),
            $base->times($days)->dividedBy($divisor)->rounded(),
        );
    }
}
