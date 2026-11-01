<?php

namespace App\Domain\Calculation\Calculators;

use App\Domain\Calculation\Contracts\ConceptCalculator;
use App\Domain\Calculation\Dto\Computation;
use App\Domain\Calculation\Dto\ComputedLines;
use App\Domain\Calculation\Dto\LiquidationContext;
use App\Domain\ValueObjects\ConceptDefinition;

final class InteresesCesantiasCalculator implements ConceptCalculator
{
    public function key(): string
    {
        return 'cesantias_interest';
    }

    public function computes(LiquidationContext $context, ComputedLines $computed, ConceptDefinition $definition): bool
    {
        return $context->days->cesantias > 0 && ! $computed->amount('cesantias')->isZero();
    }

    public function compute(LiquidationContext $context, ComputedLines $computed, ConceptDefinition $definition): Computation
    {
        $base = $computed->amount('cesantias');
        $days = $context->days->cesantias;
        $rate = $context->parameters->rate('cesantias_interest_rate');
        $divisor = $definition->defaultDivisor ?? $context->parameters->days('cesantias_divisor');
        $percent = (int) bcmul($rate->exactAmount(), '100', 4);

        return new Computation(
            $base,
            (string) $days,
            $divisor,
            sprintf('(Cesantías × Días × %d%%) / %d', $percent, $divisor),
            $base->times($days)->times($rate->exactAmount())->dividedBy($divisor)->rounded(),
        );
    }
}
