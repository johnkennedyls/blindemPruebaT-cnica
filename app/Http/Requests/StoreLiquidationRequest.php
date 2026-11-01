<?php

namespace App\Http\Requests;

use App\Domain\Enum\LiquidationVariant;
use App\Models\Contract;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreLiquidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('liquidate', $this->contract());
    }

    public function contract(): Contract
    {
        /**
         * @var Contract
         */
        $contract = $this->route('contract');

        return $contract;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'settlement_date' => ['required', 'date', 'after_or_equal:'.$this->contract()->start_date->toDateString()],
            'variant' => ['required', 'in:'.implode(',', LiquidationVariant::values())],
            'cesantias_days' => ['nullable', 'integer', 'min:0', 'max:360'],
            'prima_days' => ['nullable', 'integer', 'min:0', 'max:180'],
            'vacation_days' => ['required', 'integer', 'min:0', 'max:360'],
            'pending_salary_days' => ['required', 'integer', 'min:0', 'max:360'],
            'manual_values' => ['nullable', 'array'],
            'manual_values.*' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'observations' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'settlement_date' => 'la fecha de liquidación',
            'variant' => 'el formato de la liquidación',
            'cesantias_days' => 'los días de cesantías',
            'prima_days' => 'los días de prima',
            'vacation_days' => 'los días de vacaciones',
            'pending_salary_days' => 'los días de salario pendiente',
            'manual_values' => 'los valores digitados',
            'manual_values.*' => 'uno de los valores digitados',
            'observations' => 'las observaciones',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'settlement_date.after_or_equal' => 'La fecha de liquidación no puede ser anterior al inicio del contrato.',
            'variant.in' => 'El formato de la liquidación no es uno de los dos que maneja la calculadora.',
        ];
    }

    public function variant(): LiquidationVariant
    {
        return LiquidationVariant::from($this->text('variant'));
    }

    public function settlementDate(): string
    {
        return $this->text('settlement_date');
    }

    /**
     * @return array<string, string>
     */
    public function manualValues(): array
    {
        $values = $this->validated('manual_values');

        if (! is_array($values)) {
            return [];
        }

        $filled = array_filter(
            $values,
            static fn (mixed $value): bool => is_scalar($value) && trim((string) $value) !== ''
        );

        /** @var array<string, string> $manual */
        $manual = array_map(static fn (mixed $value): string => (string) $value, $filled);

        return $manual;
    }

    public function cesantiasDays(): ?int
    {
        return $this->days('cesantias_days');
    }

    public function primaDays(): ?int
    {
        return $this->days('prima_days');
    }

    public function vacationDays(): int
    {
        return $this->days('vacation_days') ?? 0;
    }

    public function pendingSalaryDays(): int
    {
        return $this->days('pending_salary_days') ?? 0;
    }

    public function observations(): ?string
    {
        $observations = trim($this->text('observations'));

        return $observations === '' ? null : $observations;
    }

    private function text(string $key): string
    {
        /**
         * @var string
         */
        $value = $this->validated($key) ?? '';

        return $value;
    }

    private function days(string $key): ?int
    {
        $value = $this->validated($key);

        return is_numeric($value) ? (int) $value : null;
    }
}
