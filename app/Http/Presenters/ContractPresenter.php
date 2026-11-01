<?php

namespace App\Http\Presenters;

use App\Models\Contract;
use App\Models\Liquidation;

class ContractPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function card(Contract $contract): array
    {
        $worker = $contract->worker;
        $type = $contract->contractType;

        return [
            'id' => $contract->id,
            'code' => $contract->code,
            'status' => ['value' => $contract->status->value, 'label' => $contract->status->label()],
            'can_liquidate' => $contract->status->canBeLiquidated(),
            'worker' => [
                'id' => $worker->id,
                'name' => $worker->fullName(),
                'document' => $worker->documentLabel(),
                'document_type_id' => $worker->document_type_id,
                'document_number' => $worker->document_number,
                'email' => $worker->email,
            ],
            'company' => $contract->company->business_name,
            'position' => $contract->position?->name,
            'contract_type' => $type?->name,
            'modality' => $type?->modality->label(),
            'termination_reason' => $contract->terminationReason?->description,
            'applies_indemnization' => $contract->terminationReason?->applies_indemnization === true,
            'dates' => [
                'start' => $contract->start_date->toDateString(),
                'end' => $contract->end_date?->toDateString(),
                'scheduled_end' => $contract->scheduled_end_date?->toDateString(),
            ],
            'salary' => [
                'basic' => $contract->base_salary_monthly,
                'auxiliary' => $contract->transport_auxiliary_monthly,
                'overtime_average' => $contract->overtime_average_monthly,
            ],
            'pending_days' => [
                'vacations' => $contract->pending_vacation_days,
                'salary' => $contract->pending_salary_days,
            ],
            'liquidations' => $contract->liquidations
                ->map(fn (Liquidation $liquidation): array => [
                    'id' => $liquidation->id,
                    'folio' => $liquidation->folio,
                    'net' => $liquidation->net_total,
                    'status' => $liquidation->status->label(),
                    'settlement_date' => $liquidation->settlement_date->toDateString(),
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function form(Contract $contract): array
    {
        return [
            'id' => $contract->id,
            'code' => $contract->code,
            'worker_id' => $contract->worker_id,
            'worker_name' => $contract->worker->fullName(),
            'company_id' => $contract->company_id,
            'contract_type_id' => $contract->contract_type_id,
            'position_id' => $contract->position_id,
            'termination_reason_id' => $contract->termination_reason_id,
            'creator' => $contract->creator?->name,
            'dates' => [
                'start' => $contract->start_date->toDateString(),
                'end' => $contract->end_date?->toDateString(),
                'scheduled_end' => $contract->scheduled_end_date?->toDateString(),
                'salary_effective_from' => $contract->salary_effective_from?->toDateString(),
            ],
            'base_salary_monthly' => $contract->base_salary_monthly,
            'transport_auxiliary_mode' => $contract->transport_auxiliary_mode->value,
            'transport_auxiliary_monthly' => $contract->transport_auxiliary_monthly,
            'overtime_average_monthly' => $contract->overtime_average_monthly,
            'status' => $contract->status->value,
            'pending_salary_days' => $contract->pending_salary_days,
            'pending_vacation_days' => $contract->pending_vacation_days,
            'notes' => $contract->notes,
            'liquidations_count' => $contract->liquidations()->count(),
        ];
    }
}
