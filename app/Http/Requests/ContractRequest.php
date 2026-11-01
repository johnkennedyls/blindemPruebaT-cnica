<?php

namespace App\Http\Requests;

use App\Domain\Enum\ContractStatus;
use App\Domain\Enum\TransportAuxiliaryMode;
use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contract = $this->contract();

        return $contract === null
            ? $this->user()->can('create', Contract::class)
            : $this->user()->can('update', $contract);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Z0-9._\-]+$/',
                Rule::unique('contracts', 'code')->ignore($this->contract()?->id),
            ],
            'worker_id' => ['required', 'integer', Rule::exists('workers', 'id')],
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id'), $this->companyRule()],
            'contract_type_id' => ['required', 'integer', Rule::exists('contract_types', 'id')],
            'position_id' => ['required', 'integer', Rule::exists('positions', 'id')],
            'termination_reason_id' => [
                $this->isClosed() ? 'required' : 'nullable',
                'integer',
                Rule::exists('termination_reasons', 'id'),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => [
                $this->isClosed() ? 'required' : 'nullable',
                'date',
                'after_or_equal:start_date',
            ],
            'scheduled_end_date' => [
                $this->requiresScheduledEnd() ? 'required' : 'nullable',
                'date',
                'after:start_date',
            ],
            'salary_effective_from' => ['nullable', 'date'],
            'base_salary_monthly' => ['required', 'numeric', 'gt:0', 'max:999999999.99'],
            'transport_auxiliary_mode' => ['required', Rule::enum(TransportAuxiliaryMode::class)],
            'transport_auxiliary_monthly' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'overtime_average_monthly' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'status' => ['required', Rule::enum(ContractStatus::class), $this->statusRule()],
            'pending_salary_days' => ['nullable', 'integer', 'min:0', 'max:30'],
            'pending_vacation_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['worker_id', 'start_date', 'end_date'])) {
                    return;
                }

                if ($this->overlapsAnotherContract()) {
                    $validator->errors()->add(
                        'start_date',
                        'El trabajador ya tiene un contrato que cubre ese periodo. Un trabajador no puede tener dos contratos solapados.',
                    );
                }
            },
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['contract_type_id', 'start_date', 'scheduled_end_date'])) {
                    return;
                }

                $message = $this->statutoryTermMessage();

                if ($message !== null) {
                    $validator->errors()->add('scheduled_end_date', $message);
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'El contrato necesita un código propio para poder rastrearse.',
            'code.regex' => 'El código solo acepta letras mayúsculas, números, punto, guion bajo y guion.',
            'code.unique' => 'Ya existe un contrato registrado con ese código.',
            'worker_id.exists' => 'El trabajador seleccionado no está registrado en Blindem.',
            'company_id.exists' => 'La empresa seleccionada no está registrada en Blindem.',
            'start_date.required' => 'La fecha de ingreso es obligatoria para poder calcular las prestaciones.',
            'end_date.after_or_equal' => 'La fecha de retiro no puede ser anterior a la fecha de ingreso.',
            'scheduled_end_date.after' => 'La fecha de terminación prevista debe ser posterior a la fecha de ingreso.',
            'termination_reason_id.required' => 'Un contrato cerrado debe indicar la causal de terminación.',
            'end_date.required' => 'Un contrato cerrado debe indicar la fecha de retiro.',
            'base_salary_monthly.gt' => 'El salario mensual básico debe ser mayor que cero.',
            'pending_salary_days.max' => 'Los días de salario pendiente no pueden superar 30.',
            'pending_vacation_days.max' => 'Los días de vacaciones pendientes no pueden superar 365.',
            'scheduled_end_date.required' => 'El tipo de contrato seleccionado exige una fecha de terminación prevista.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => 'el código',
            'worker_id' => 'el trabajador',
            'company_id' => 'la empresa',
            'contract_type_id' => 'el tipo de contrato',
            'position_id' => 'el cargo',
            'termination_reason_id' => 'la causal de terminación',
            'start_date' => 'la fecha de ingreso',
            'end_date' => 'la fecha de retiro',
            'scheduled_end_date' => 'la fecha de terminación prevista',
            'base_salary_monthly' => 'el salario mensual básico',
            'status' => 'el estado',
        ];
    }

    protected function prepareForValidation(): void
    {
        $closed = $this->isClosed();
        $mode = TransportAuxiliaryMode::tryFrom((string) $this->input('transport_auxiliary_mode'));

        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'end_date' => $closed ? $this->input('end_date') : null,
            'termination_reason_id' => $closed ? $this->input('termination_reason_id') : null,
            'transport_auxiliary_monthly' => $mode === TransportAuxiliaryMode::SinDerecho
                ? '0.00'
                : $this->numericOrZero('transport_auxiliary_monthly'),
            'overtime_average_monthly' => $this->numericOrZero('overtime_average_monthly'),
            'pending_salary_days' => $this->numericOrZero('pending_salary_days'),
            'pending_vacation_days' => $this->numericOrZero('pending_vacation_days'),
        ]);
    }

    public function contract(): ?Contract
    {
        $route = $this->route('contract');

        return $route instanceof Contract ? $route : null;
    }

    private function isClosed(): bool
    {
        return ContractStatus::tryFrom((string) $this->input('status'))?->isClosed() === true;
    }

    private function selectedType(): ?ContractType
    {
        $id = $this->input('contract_type_id');

        return is_numeric($id) ? ContractType::query()->find((int) $id) : null;
    }

    private function requiresScheduledEnd(): bool
    {
        return $this->selectedType()?->requires_scheduled_end === true;
    }

    private function statutoryTermMessage(): ?string
    {
        $type = $this->selectedType();
        $scheduled = $this->input('scheduled_end_date');

        if (! $type instanceof ContractType || $type->statutory_max_months === null) {
            return null;
        }

        if (! is_string($scheduled) || $scheduled === '') {
            return null;
        }

        $limit = Carbon::parse((string) $this->input('start_date'))
            ->addMonths($type->statutory_max_months);

        if (Carbon::parse($scheduled)->lte($limit)) {
            return null;
        }

        return sprintf(
            'El tipo %s admite un plazo máximo de %d meses. La fecha de terminación prevista pasa de ese límite.',
            $type->name,
            $type->statutory_max_months,
        );
    }

    private function overlapsAnotherContract(): bool
    {
        $start = (string) $this->input('start_date');
        $end = $this->input('end_date');
        $end = is_string($end) && $end !== '' ? $end : '9999-12-31';

        return Contract::query()
            ->where('worker_id', (int) $this->input('worker_id'))
            ->when(
                $this->contract(),
                fn (Builder $query, Contract $current): Builder => $query->whereKeyNot($current->id),
            )
            ->where('start_date', '<=', $end)
            ->where(
                fn (Builder $query): Builder => $query
                    ->whereNull('end_date')
                    ->orWhere('end_date', '>=', $start),
            )
            ->exists();
    }

    private function numericOrZero(string $key): string|int
    {
        $value = $this->input($key);

        return is_numeric($value) ? (string) $value : 0;
    }

    private function companyRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $user = $this->user();

            if (! $user instanceof User || $user->isAdmin()) {
                return;
            }

            if (! Company::query()->whereKey($value)->whereHas(
                'users',
                fn (Builder $query): Builder => $query->whereKey($user->id),
            )->exists()) {
                $fail('Solo puede registrar contratos para las empresas en las que colabora.');
            }
        };
    }

    private function statusRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $status = ContractStatus::tryFrom((string) $value);

            if ($status === ContractStatus::Liquidado) {
                $fail('El estado Liquidado lo fija el sistema al guardar la liquidación, no se asigna a mano.');

                return;
            }

            if ($status === ContractStatus::Activo && $this->contract()?->liquidations()->exists()) {
                $fail('Un contrato con liquidaciones registradas no puede volver a estar activo.');
            }
        };
    }
}
