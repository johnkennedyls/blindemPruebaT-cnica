<?php

namespace App\Http\Parametrics;

/**
 * A single editable column of a parametric catalog, declared by
 * ParametricDefinition and consumed by ParametricController and
 * the shared Inertia form builder.
 */
final class ParametricField
{
    /**
     * @param  array<int, array{value: string, label: string}>|null  $options
     */
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly FieldType $kind,
        public readonly bool $required = false,
        public readonly ?array $options = null,
        public readonly ?string $hint = null,
        public readonly ?int $maxLength = null,
        public readonly ?float $max = null,
        public readonly mixed $default = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toFrontend(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'kind' => $this->kind->value,
            'required' => $this->required,
            'options' => $this->options,
            'hint' => $this->hint,
            'max_length' => $this->maxLength,
            'default' => $this->default,
        ];
    }
}
