<?php

namespace App\Http\Presenters;

use App\Models\Worker;

class WorkerPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function card(Worker $worker): array
    {
        return [
            'id' => $worker->id,
            'name' => $worker->fullName(),
            'document' => $worker->documentLabel(),
            'first_names' => $worker->first_names,
            'last_names' => $worker->last_names,
            'document_type_id' => $worker->document_type_id,
            'document_number' => $worker->document_number,
            'birth_date' => $worker->birth_date?->toDateString(),
            'gender' => $worker->gender?->value,
            'email' => $worker->email,
            'phone' => $worker->phone,
            'address' => $worker->address,
            'city' => $worker->city,
            'department' => $worker->department,
            'is_active' => $worker->is_active,
            'document_type' => $worker->documentType?->name,
            'contracts_count' => $worker->getAttributeValue('contracts_count'),
        ];
    }
}
