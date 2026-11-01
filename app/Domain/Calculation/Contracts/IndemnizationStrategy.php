<?php

namespace App\Domain\Calculation\Contracts;

use App\Domain\Calculation\Dto\IndemnizableDays;
use App\Domain\Calculation\Dto\LiquidationContext;
use App\Domain\Enum\IndemnizationRule;

interface IndemnizationStrategy
{
    public function rule(): IndemnizationRule;

    public function applies(LiquidationContext $context): bool;

    public function days(LiquidationContext $context): IndemnizableDays;

    public function formulaText(LiquidationContext $context): string;
}
