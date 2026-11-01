<?php

namespace App\Models;

use App\Domain\Enum\BaseKind;
use App\Domain\Enum\ConceptSide;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ConceptCatalog extends Model
{
    protected $fillable = [
        'code',
        'name',
        'side',
        'formula_key',
        'base_kind',
        'default_divisor',
        'applies_ss_contribution',
        'is_statutory',
        'is_system',
        'default_sequence',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'side' => ConceptSide::class,
            'base_kind' => BaseKind::class,
            'default_divisor' => 'integer',
            'default_sequence' => 'integer',
            'applies_ss_contribution' => 'boolean',
            'is_statutory' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('default_sequence')->orderBy('name');
    }

    public function isEditable(): bool
    {
        return ! $this->is_system;
    }
}
