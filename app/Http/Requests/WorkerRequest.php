<?php

namespace App\Http\Requests;

use App\Domain\Enum\Gender;
use App\Models\Worker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $worker = $this->worker();

        return $worker === null
            ? $this->user()->can('create', Worker::class)
            : $this->user()->can('update', $worker);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_type_id' => ['required', 'integer', Rule::exists('document_types', 'id')],
            'document_number' => [
                'required',
                'string',
                'max:30',
                'regex:/^[0-9]+([.\-]?[0-9]+)*$/',
                Rule::unique('workers', 'document_number')
                    ->where('document_type_id', $this->input('document_type_id'))
                    ->ignore($this->worker()?->id),
            ],
            'first_names' => ['required', 'string', 'max:120'],
            'last_names' => ['required', 'string', 'max:120'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:250'],
            'city' => ['nullable', 'string', 'max:60'],
            'department' => ['nullable', 'string', 'max:60'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_number.regex' => 'El número de documento solo acepta dígitos y separadores.',
            'document_number.unique' => 'Ya existe un trabajador registrado con ese tipo y número de documento.',
            'birth_date.before' => 'La fecha de nacimiento no puede ser futura.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'document_type_id' => 'tipo de documento',
            'document_number' => 'número de documento',
            'first_names' => 'nombres',
            'last_names' => 'apellidos',
        ];
    }

    private function worker(): ?Worker
    {
        $route = $this->route('worker');

        return $route instanceof Worker ? $route : null;
    }
}
