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
}
