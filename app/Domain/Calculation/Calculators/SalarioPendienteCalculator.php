<?php

namespace App\Domain\Calculation\Calculators;

use App\Domain\Calculation\Contracts\ConceptCalculator;
use App\Domain\Calculation\Dto\Computation;
use App\Domain\Calculation\Dto\ComputedLines;
use App\Domain\Calculation\Dto\LiquidationContext;
use App\Domain\ValueObjects\ConceptDefinition;

final class SalarioPendienteCalculator implements ConceptCalculator
{
    public function key(): string
    {
        return 'pending_salary';
    }

    public function computes(LiquidationContext $context, ComputedLines $computed, ConceptDefinition $definition): bool
    {
        return $context->days->pendingSalary > 0;
    }

    public function compute(LiquidationContext $context, ComputedLines $computed, ConceptDefinition $definition): Computation
    {
        $base = $context->bases->basic;
        $days = $context->days->pendingSalary;
        $divisor = $definition->defaultDivisor ?? $context->parameters->days('commercial_month_days');

        return new Computation(
            $base,
            (string) $days,
            $divisor,
            sprintf('(Salario Mensual × Días) / %d', $divisor),
            $base->times($days)->dividedBy($divisor)->rounded(),
        );
    }
}
