<?php

namespace App\Models;

use App\Domain\Enum\IndemnizationRule;
use App\Domain\Enum\TerminationClassification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $description
 * @property TerminationClassification $classification
 * @property bool $applies_indemnization
 * @property IndemnizationRule $indemnization_rule
 * @property bool $applies_sanction_art65
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
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

    /**
     * @param  Builder<TerminationReason>  $query
     * @return Builder<TerminationReason>
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
