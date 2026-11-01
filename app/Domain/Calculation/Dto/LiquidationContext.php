<?php

namespace App\Domain\Calculation\Dto;

use App\Domain\Calculation\Contracts\IndemnizationStrategy;
use App\Domain\Calculation\LiquidationDraft;
use App\Domain\Calculation\Parameters;
use App\Domain\Enum\LiquidationVariant;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\WageSet;

final class LiquidationContext
{
    public function __construct(
        public readonly LiquidationDraft $draft,
        public readonly SalaryBases $bases,
        public readonly DayCounts $days,
        public readonly Tenure $tenure,
        public readonly WageSet $wages,
        public readonly Parameters $parameters,
        public readonly IndemnizationStrategy $strategy,
        public readonly int $remainingTermDays,
    ) {}

    public function variant(): LiquidationVariant
    {
        return $this->draft->variant;
    }

    public function dailyWage(): Money
    {
        return $this->bases->basic->dividedBy($this->parameters->days('commercial_month_days'));
    }
}
