<?php

namespace App\Domain\Calculation\Contracts;

use App\Domain\Calculation\Dto\Computation;
use App\Domain\Calculation\Dto\ComputedLines;
use App\Domain\Calculation\Dto\LiquidationContext;
use App\Domain\ValueObjects\ConceptDefinition;

interface ConceptCalculator
{
    public function key(): string;

    public function computes(LiquidationContext $context, ComputedLines $computed, ConceptDefinition $definition): bool;

    public function compute(LiquidationContext $context, ComputedLines $computed, ConceptDefinition $definition): Computation;
}
