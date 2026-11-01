<?php

namespace App\Models;

use App\Domain\Enum\ContractModality;
use App\Domain\Enum\IndemnizationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'modality',
        'requires_scheduled_end',
        'statutory_max_months',
        'applies_indemnization',
        'indemnization_rule',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'modality' => ContractModality::class,
            'indemnization_rule' => IndemnizationRule::class,
            'requires_scheduled_end' => 'boolean',
            'applies_indemnization' => 'boolean',
            'is_active' => 'boolean',
            'statutory_max_months' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
