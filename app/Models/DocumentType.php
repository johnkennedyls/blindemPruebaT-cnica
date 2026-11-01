<?php

namespace App\Models;

use App\Domain\Enum\HolderType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $abbreviation
 * @property HolderType $holder_type
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DocumentType extends Model implements ParametricCatalog
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

    /**
     * @param  Builder<DocumentType>  $query
     * @return Builder<DocumentType>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return HasMany<Worker, $this>
     */
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

    public function detailLabel(): string
    {
        return $this->holder_type->label();
    }

    public function formatNumber(string $documentNumber): string
    {
        return $this->abbreviation
            ? "{$this->abbreviation} {$documentNumber}"
            : $documentNumber;
    }
}
