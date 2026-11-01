<?php

namespace App\Models;

use App\Domain\Enum\ContractModality;
use App\Domain\Enum\IndemnizationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property ContractModality $modality
 * @property bool $requires_scheduled_end
 * @property int|null $statutory_max_months
 * @property bool $applies_indemnization
 * @property IndemnizationRule|null $indemnization_rule
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
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

    /**
     * @param  Builder<ContractType>  $query
     * @return Builder<ContractType>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return HasMany<Contract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
