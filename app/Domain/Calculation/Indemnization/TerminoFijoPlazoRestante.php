<?php

namespace App\Domain\Calculation\Indemnization;

use App\Domain\Calculation\Contracts\IndemnizationStrategy;
use App\Domain\Calculation\Dto\IndemnizableDays;
use App\Domain\Calculation\Dto\LiquidationContext;
use App\Domain\Enum\IndemnizationRule;
use App\Domain\ValueObjects\Money;

final class TerminoFijoPlazoRestante implements IndemnizationStrategy
{
    public function rule(): IndemnizationRule
    {
        return IndemnizationRule::PlazoRestante;
    }

    public function applies(LiquidationContext $context): bool
    {
        return $context->remainingTermDays > 0;
    }

    public function days(LiquidationContext $context): IndemnizableDays
    {
        return IndemnizableDays::single(
            Money::of($context->remainingTermDays),
            sprintf('Salarios faltantes por %d días hasta la fecha pactada de vencimiento', $context->remainingTermDays),
        );
    }

    public function formulaText(LiquidationContext $context): string
    {
        return '(Salario Básico × Días Faltantes del Plazo) / 30';
    }
}
