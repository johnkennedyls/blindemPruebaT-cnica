<?php

namespace App\Domain\Calculation\Calculators;

use App\Domain\Calculation\Contracts\ConceptCalculator;
use App\Domain\Calculation\Dto\Computation;
use App\Domain\Calculation\Dto\ComputedLines;
use App\Domain\Calculation\Dto\LiquidationContext;
use App\Domain\ValueObjects\ConceptDefinition;

final class ManualValueCalculator implements ConceptCalculator
{
    public function key(): string
    {
        return 'manual';
    }

    public function computes(LiquidationContext $context, ComputedLines $computed, ConceptDefinition $definition): bool
    {
        return $context->draft->hasManualValue($definition->code);
    }

    public function compute(LiquidationContext $context, ComputedLines $computed, ConceptDefinition $definition): Computation
    {
        $value = $context->draft->manualValue($definition->code);

        return new Computation($value, '1', null, 'Valor digitado por el usuario', $value->rounded());
    }
}
