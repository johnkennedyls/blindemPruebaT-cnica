<?php

namespace App\Domain\Calculation\Indemnization;

use App\Domain\Calculation\Contracts\IndemnizationStrategy;
use App\Domain\Calculation\Dto\IndemnizableDays;
use App\Domain\Calculation\Dto\LiquidationContext;
use App\Domain\Enum\IndemnizationRule;
use App\Domain\ValueObjects\Money;

abstract class ProportionalByYears implements IndemnizationStrategy
{
    protected function __construct(
        private readonly IndemnizationRule $rule,
        private readonly string $firstYearKey,
        private readonly string $perYearKey,
    ) {}

    public function rule(): IndemnizationRule
    {
        return $this->rule;
    }

    public function applies(LiquidationContext $context): bool
    {
        return true;
    }

    public function days(LiquidationContext $context): IndemnizableDays
    {
        $first = $context->parameters->money($this->firstYearKey);
        $perYear = $context->parameters->money($this->perYearKey);
        $tenure = $context->tenure;

        $following = $perYear->times(max(0, $tenure->completeYears - 1));
        $fraction = Money::of($tenure->fractionDays)->times($perYear->exactAmount())->dividedBy($tenure->yearDays);

        return IndemnizableDays::of(
            $first,
            $following,
            $fraction,
            sprintf(
                '%s días el primer año + %s por años siguientes + %s por la fracción de %s/%s',
                $first->displayAmount(),
                $following->displayAmount(),
                $fraction->displayAmount(),
                $tenure->fractionDays,
                $tenure->yearDays,
            ),
        );
    }

    public function formulaText(LiquidationContext $context): string
    {
        return '(Salario Básico × Días Indemnizables) / 30';
    }
}
