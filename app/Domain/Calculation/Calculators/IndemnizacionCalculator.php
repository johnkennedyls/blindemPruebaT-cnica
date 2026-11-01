<?php

namespace App\Domain\Calculation\Calculators;

use App\Domain\Calculation\Contracts\ConceptCalculator;
use App\Domain\Calculation\Dto\Computation;
use App\Domain\Calculation\Dto\ComputedLines;
use App\Domain\Calculation\Dto\LiquidationContext;
use App\Domain\ValueObjects\ConceptDefinition;
use App\Domain\ValueObjects\Money;

final class IndemnizacionCalculator implements ConceptCalculator
{
    public function key(): string
    {
        return 'indemnization';
    }

    public function computes(LiquidationContext $context, ComputedLines $computed, ConceptDefinition $definition): bool
    {
        return true;
    }

    public function compute(LiquidationContext $context, ComputedLines $computed, ConceptDefinition $definition): Computation
    {
        $base = $context->bases->basic;
        $divisor = $definition->defaultDivisor ?? $context->parameters->days('commercial_month_days');

        if (! $context->variant()->includesIndemnization() || ! $context->strategy->applies($context)) {
            return new Computation($base, '0', $divisor, 'No aplica según el motivo de retiro', Money::zero());
        }

        $days = $context->strategy->days($context);

        return new Computation(
            $base,
            $days->amount(),
            $divisor,
            $context->strategy->formulaText($context),
            $base->times($days->amount())->dividedBy($divisor)->rounded(),
        );
    }
}
