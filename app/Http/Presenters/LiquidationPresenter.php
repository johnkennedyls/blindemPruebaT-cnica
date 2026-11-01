<?php

namespace App\Http\Presenters;

use App\Domain\Enum\IndemnizationRule;
use App\Models\Liquidation;
use App\Models\LiquidationItem;

class LiquidationPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function document(Liquidation $liquidation): array
    {
        $snapshot = $liquidation->params_snapshot;

        return [
            'id' => $liquidation->id,
            'folio' => $liquidation->folio,
            'status' => ['value' => $liquidation->status->value, 'label' => $liquidation->status->label()],
            'variant' => ['value' => $liquidation->variant->value, 'label' => $liquidation->variant->label()],
            'observations' => $liquidation->observations,
            'creator' => $liquidation->creator?->name,
            'dates' => [
                'settlement' => $liquidation->settlement_date->toDateString(),
                'period_from' => $liquidation->period_from?->toDateString(),
                'period_to' => $liquidation->period_to?->toDateString(),
            ],
            'bases' => [
                'prestacional' => $liquidation->base_prestacional_snapshot,
                'vacacional' => $liquidation->base_vacacional_snapshot,
                'smlmv' => $liquidation->smlmv_snapshot,
            ],
            'days' => [
                'cesantias' => $liquidation->cesantia_days,
                'prima' => $liquidation->prima_days,
                'vacaciones' => $liquidation->vacation_days,
                'salario_pendiente' => $liquidation->pending_salary_days,
                'worked' => $liquidation->total_days_worked,
            ],
            'lines' => $liquidation->items
                ->map(fn (LiquidationItem $item): array => [
                    'code' => $item->concept_code,
                    'name' => $item->concept_name,
                    'side' => ['value' => $item->side->value, 'label' => $item->side->label()],
                    'base' => $item->calculation_base,
                    'quantity' => $item->quantity,
                    'divisor' => $item->divisor,
                    'formula' => $item->formula_text,
                    'trace' => $item->calc_trace,
                    'amount' => $item->amount,
                    'accrues_social_security' => $item->applies_ss_contribution,
                ])
                ->all(),
            'totals' => [
                'earned' => $liquidation->total_earned,
                'deducted' => $liquidation->total_deductions,
                'net' => $liquidation->net_total,
            ],
            'notices' => $snapshot['avisos'] ?? [],
            'indemnization' => self::indemnization($snapshot),
            'tenure' => $snapshot['antiguedad'] ?? null,
            'wages' => $snapshot['minimum_wage'] ?? null,
            'parameters' => $snapshot['legal_parameters'] ?? [],
            'captured_at' => $snapshot['captured_at'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>|null
     */
    private static function indemnization(array $snapshot): ?array
    {
        $indemnization = $snapshot['indemnizacion'] ?? null;

        if (! is_array($indemnization)) {
            return null;
        }

        $rule = IndemnizationRule::tryFrom((string) ($indemnization['regla'] ?? ''));

        if ($rule !== null) {
            $indemnization['regla'] = $rule->label();
        }

        return $indemnization;
    }
}
