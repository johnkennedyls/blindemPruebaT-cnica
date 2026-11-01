<?php

namespace App\Domain\Calculation\Indemnization;

use App\Domain\Calculation\Contracts\IndemnizationStrategy;
use App\Domain\Calculation\Dto\IndemnizableDays;
use App\Domain\Calculation\Dto\LiquidationContext;
use App\Domain\Enum\IndemnizationRule;

final class SinIndemnizacion implements IndemnizationStrategy
{
    public function rule(): IndemnizationRule
    {
        return IndemnizationRule::Ninguna;
    }

    public function applies(LiquidationContext $context): bool
    {
        return false;
    }

    public function days(LiquidationContext $context): IndemnizableDays
    {
        return IndemnizableDays::zero();
    }

    public function formulaText(LiquidationContext $context): string
    {
        return 'No aplica según el motivo de retiro';
    }
}
