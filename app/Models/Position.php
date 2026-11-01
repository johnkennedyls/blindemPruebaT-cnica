<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

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
}
