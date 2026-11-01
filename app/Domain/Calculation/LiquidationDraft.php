<?php

namespace App\Domain\Calculation;

use App\Domain\Enum\ContractModality;
use App\Domain\Enum\IndemnizationRule;
use App\Domain\Enum\LiquidationVariant;
use App\Domain\Enum\TransportAuxiliaryMode;
use App\Domain\ValueObjects\Money;

final class LiquidationDraft
{
    /**
     * @param  array<string, string>  $manualValues
     */
    public function __construct(
        public readonly LiquidationVariant $variant,
        public readonly string $startDate,
        public readonly string $settlementDate,
        public readonly Money $baseSalary,
        public readonly Money $transportAuxiliary,
        public readonly Money $overtimeAverage,
        public readonly TransportAuxiliaryMode $transportMode,
        public readonly ContractModality $modality,
        public readonly IndemnizationRule $indemnizationRule,
        public readonly ?string $scheduledEndDate = null,
        public readonly ?int $cesantiasDays = null,
        public readonly ?int $primaDays = null,
        public readonly int $vacationDays = 0,
        public readonly int $pendingSalaryDays = 0,
        public readonly array $manualValues = [],
    ) {}

    public function hasManualValue(string $conceptCode): bool
    {
        return array_key_exists($conceptCode, $this->manualValues);
    }

    public function manualValue(string $conceptCode): Money
    {
        return Money::of($this->manualValues[$conceptCode] ?? '0');
    }
}
