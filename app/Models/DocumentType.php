<?php

namespace App\Models;

use App\Domain\Enum\HolderType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'abbreviation',
        'holder_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'holder_type' => HolderType::class,
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function workers(): HasMany
    {
        return $this->hasMany(Worker::class);
    }

    public function optionLabel(): string
    {
        return $this->abbreviation
            ? "{$this->abbreviation} · {$this->name}"
            : $this->name;
    }

    public function formatNumber(string $documentNumber): string
    {
        return $this->abbreviation
            ? "{$this->abbreviation} {$documentNumber}"
            : $documentNumber;
    }
}
