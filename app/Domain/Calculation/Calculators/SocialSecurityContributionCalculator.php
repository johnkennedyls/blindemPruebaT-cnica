<?php

namespace App\Domain\Calculation\Calculators;

use App\Domain\Calculation\Contracts\ConceptCalculator;
use App\Domain\Calculation\Dto\Computation;
use App\Domain\Calculation\Dto\ComputedLines;
use App\Domain\Calculation\Dto\LiquidationContext;
use App\Domain\ValueObjects\ConceptDefinition;

final class SocialSecurityContributionCalculator implements ConceptCalculator
{
    public function __construct(
        private readonly string $formulaKey,
        private readonly string $parameterKey,
    ) {}

    public function key(): string
    {
        return $this->formulaKey;
    }

    public function computes(LiquidationContext $context, ComputedLines $computed, ConceptDefinition $definition): bool
    {
        return ! $computed->socialSecurityBase()->isZero();
    }

    public function compute(LiquidationContext $context, ComputedLines $computed, ConceptDefinition $definition): Computation
    {
        $base = $computed->socialSecurityBase();
        $rate = $context->parameters->rate($this->parameterKey);
        $percent = (int) bcmul($rate->exactAmount(), '100', 4);

        return new Computation(
            $base,
            $rate->exactAmount(),
            null,
            sprintf('%d%% sobre salario pendiente y vacaciones', $percent),
            $base->times($rate->exactAmount())->rounded(),
        );
    }
}
