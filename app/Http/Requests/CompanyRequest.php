<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = $this->company();

        return $company === null
            ? $this->user()->can('create', Company::class)
            : $this->user()->can('update', $company);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nit' => [
                'required',
                'string',
                'min:8',
                'max:15',
                'regex:/^[0-9]+(-?[0-9]+)?$/',
                Rule::unique('companies', 'nit')->ignore($this->company()?->id),
            ],
            'business_name' => ['required', 'string', 'max:200'],
            'trade_name' => ['nullable', 'string', 'max:200'],
            'activity_code' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:250'],
            'city' => ['nullable', 'string', 'max:60'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:160'],
            'legal_rep_name' => ['nullable', 'string', 'max:200'],
            'legal_rep_document' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nit.regex' => 'El NIT solo acepta dígitos y un guion opcional.',
            'nit.unique' => 'Ya existe una empresa registrada con ese NIT.',
        ];
    }

    private function company(): ?Company
    {
        $route = $this->route('company');

        return $route instanceof Company ? $route : null;
    }
}
