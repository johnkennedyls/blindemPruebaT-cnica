<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $area
 * @property string|null $level
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Position extends Model implements ParametricCatalog
{
    protected $fillable = [
        'code',
        'name',
        'area',
        'level',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Position>  $query
     * @return Builder<Position>
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

    public function optionLabel(): string
    {
        return $this->area
            ? "{$this->name} · {$this->area}"
            : $this->name;
    }

    public function detailLabel(): string
    {
        return implode(' · ', array_filter([
            $this->area,
            $this->level !== null ? "Nivel {$this->level}" : null,
        ]));
    }
}
