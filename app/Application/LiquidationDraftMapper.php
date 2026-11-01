<?php

namespace App\Application;

use App\Domain\Calculation\LiquidationDraft;
use App\Domain\Enum\IndemnizationRule;
use App\Domain\Enum\LiquidationVariant;
use App\Domain\Exceptions\ContractNotLiquidatable;
use App\Domain\ValueObjects\Money;
use App\Models\Contract;
use App\Models\ContractType;

final class LiquidationDraftMapper
{
    /**
     * @param  array<string, string>  $manualValues
     */
    public function fromContract(
        Contract $contract,
        string $settlementDate,
        LiquidationVariant $variant,
        int $vacationDays,
        int $pendingSalaryDays,
        array $manualValues = [],
        ?int $cesantiasDays = null,
        ?int $primaDays = null,
    ): LiquidationDraft {
        $type = $contract->contractType;

        if ($type === null) {
            throw ContractNotLiquidatable::missingType();
        }

        return new LiquidationDraft(
            variant: $variant,
            startDate: $contract->start_date->toDateString(),
            settlementDate: $settlementDate,
            baseSalary: Money::of($contract->base_salary_monthly),
            transportAuxiliary: Money::of($contract->transport_auxiliary_monthly),
            overtimeAverage: Money::of($contract->overtime_average_monthly),
            transportMode: $contract->transport_auxiliary_mode,
            modality: $type->modality,
            indemnizationRule: $this->rule($contract, $type, $variant),
            scheduledEndDate: $contract->scheduled_end_date?->toDateString(),
            cesantiasDays: $cesantiasDays,
            primaDays: $primaDays,
            vacationDays: $vacationDays,
            pendingSalaryDays: $pendingSalaryDays,
            manualValues: $manualValues,
        );
    }

    public function suggestsVariant(Contract $contract): LiquidationVariant
    {
        return $contract->terminationReason?->applies_indemnization === true
            ? LiquidationVariant::ConIndemnizacion
            : LiquidationVariant::Prestacional;
    }

    private function rule(Contract $contract, ContractType $type, LiquidationVariant $variant): IndemnizationRule
    {
        if (! $variant->includesIndemnization()) {
            return IndemnizationRule::Ninguna;
        }

        $reason = $contract->terminationReason;

        if ($reason === null || ! $reason->applies_indemnization) {
            return IndemnizationRule::Ninguna;
        }

        return $reason->indemnization_rule
            ?? $type->indemnization_rule
            ?? IndemnizationRule::Art64SegunSalario;
    }
}
