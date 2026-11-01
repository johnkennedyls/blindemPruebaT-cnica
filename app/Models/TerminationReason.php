<?php

namespace App\Models;

use App\Domain\Enum\IndemnizationRule;
use App\Domain\Enum\TerminationClassification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TerminationReason extends Model
{
    protected $fillable = [
        'code',
        'description',
        'classification',
        'applies_indemnization',
        'indemnization_rule',
        'applies_sanction_art65',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'classification' => TerminationClassification::class,
            'indemnization_rule' => IndemnizationRule::class,
            'applies_indemnization' => 'boolean',
            'applies_sanction_art65' => 'boolean',
            'is_active' => 'boolean',
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
