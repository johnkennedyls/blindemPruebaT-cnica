<?php

namespace App\Models;

use App\Domain\Enum\Gender;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Worker extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_type_id',
        'document_number',
        'first_names',
        'last_names',
        'birth_date',
        'gender',
        'email',
        'phone',
        'address',
        'city',
        'department',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'document_type_id' => 'integer',
            'birth_date' => 'date',
            'gender' => Gender::class,
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeNamed(Builder $query, string $term): Builder
    {
        return $query->whereRaw("(first_names || ' ' || last_names) ILIKE ?", ['%'.$term.'%']);
    }

    public function scopeByDocument(Builder $query, string $number, ?int $documentTypeId = null): Builder
    {
        $query->where('document_number', trim($number));

        return $documentTypeId === null
            ? $query
            : $query->where('document_type_id', $documentTypeId);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function fullName(): string
    {
        return trim("{$this->first_names} {$this->last_names}");
    }

    public function documentLabel(): string
    {
        $type = $this->documentType;

        return $type === null
            ? $this->document_number
            : $type->formatNumber($this->document_number);
    }

    public function optionLabel(): string
    {
        return "{$this->fullName()} · {$this->document_number}";
    }
}
