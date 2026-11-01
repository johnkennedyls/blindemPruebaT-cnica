<?php

namespace App\Http\Requests;

use App\Http\Parametrics\FieldType;
use App\Http\Parametrics\ParametricDefinition;
use App\Http\Parametrics\ParametricField;
use App\Http\Parametrics\ParametricRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * One form request for every parametric catalog: the list of fields,
 * unique attribute and derived columns come from the definition that
 * the controller resolved out of the {resource} URL segment.
 */
class ParametricRequest extends FormRequest
{
    private ?ParametricDefinition $definition = null;

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [];

        foreach ($this->definition()->fields as $field) {
            $rules[$field->name] = $this->fieldRules($field);
        }

        if ($this->definition()->uniqueAttribute !== null) {
            $rules[$this->definition()->uniqueAttribute][] = Rule::unique($this->definition()->table)
                ->ignore($this->route('parametric'));
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        if ($this->definition()->uniqueAttribute === null) {
            return [];
        }

        return [
            $this->definition()->uniqueAttribute.'.unique' => sprintf(
                'Ya existe un %s con ese %s.',
                $this->definition()->one,
                $this->definition()->uniqueLabel,
            ),
        ];
    }

    private function definition(): ParametricDefinition
    {
        return $this->definition ??= ParametricRegistry::resolve((string) $this->route('resource'));
    }

    /**
     * @return array<int, mixed>
     */
    private function fieldRules(ParametricField $field): array
    {
        return match ($field->kind) {
            FieldType::Text => [
                $field->required ? 'required' : 'nullable',
                'string',
                'max:'.($field->maxLength ?? 200),
            ],
            FieldType::Number => [
                $field->required ? 'required' : 'nullable',
                'integer',
                'min:0',
                ...($field->max !== null ? ['max:'.(int) $field->max] : []),
            ],
            FieldType::Select => [
                $field->required ? 'required' : 'nullable',
                Rule::in(array_column($field->options ?? [], 'value')),
            ],
            FieldType::Checkbox => ['boolean'],
        };
    }

    protected function prepareForValidation(): void
    {
        $definition = $this->definition();
        $cleaned = $this->all();

        foreach ($definition->fields as $field) {
            $value = $cleaned[$field->name] ?? null;
            $cleaned[$field->name] = match ($field->kind) {
                FieldType::Text => is_string($value) ? trim($value) : $value,
                FieldType::Number => is_numeric($value) ? (int) $value : null,
                FieldType::Select => (is_string($value) && $value !== '') ? $value : null,
                FieldType::Checkbox => filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) === true,
            };
        }

        $this->merge($cleaned);
    }
}
