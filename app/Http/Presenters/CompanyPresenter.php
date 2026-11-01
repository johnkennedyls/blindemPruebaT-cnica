<?php

namespace App\Http\Presenters;

use App\Models\Company;

class CompanyPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function card(Company $company): array
    {
        return [
            'id' => $company->id,
            'nit' => $company->nit,
            'business_name' => $company->business_name,
            'trade_name' => $company->trade_name,
            'activity_code' => $company->activity_code,
            'address' => $company->address,
            'city' => $company->city,
            'phone' => $company->phone,
            'email' => $company->email,
            'legal_rep_name' => $company->legal_rep_name,
            'legal_rep_document' => $company->legal_rep_document,
            'is_active' => $company->is_active,
            'contracts_count' => $company->getAttributeValue('contracts_count'),
        ];
    }
}
