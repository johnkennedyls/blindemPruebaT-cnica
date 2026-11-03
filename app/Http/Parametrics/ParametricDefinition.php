<?php

namespace App\Http\Parametrics;

use App\Models\ParametricCatalog;
use Illuminate\Database\Eloquent\Model;

final class ParametricDefinition
{
    /**
     * @param  class-string<Model&ParametricCatalog>  $model
     * @param  array<int, ParametricField>  $fields
     * @param  array<int, string>  $searchAttributes
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $model,
        public readonly string $table,
        public readonly string $title,
        public readonly string $one,
        public readonly string $description,
        public readonly array $fields,
        public readonly string $headline,
        public readonly string $headlineLabel,
        public readonly ?string $uniqueAttribute,
        public readonly ?string $uniqueLabel,
        public readonly ?string $primaryAttribute,
        public readonly ?string $usageRelation,
        public readonly ?string $usageLabel,
        public readonly bool $hasPauseState = true,
        public readonly array $searchAttributes = ['code', 'name'],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toFrontend(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'one' => $this->one,
            'description' => $this->description,
            'headline' => $this->headline,
            'headline_label' => $this->headlineLabel,
            'has_pause_state' => $this->hasPauseState,
            'usage_label' => $this->usageLabel,
            'fields' => array_map(
                fn (ParametricField $field): array => $field->toFrontend(),
                $this->fields,
            ),
        ];
    }
}
