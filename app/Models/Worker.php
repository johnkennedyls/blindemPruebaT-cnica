<?php

namespace App\Models;

use App\Domain\Enum\Gender;
use Database\Factories\WorkerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $document_type_id
 * @property string $document_number
 * @property string $first_names
 * @property string $last_names
 * @property Carbon|null $birth_date
 * @property Gender|null $gender
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $city
 * @property string|null $department
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Worker extends Model
{
    /** @use HasFactory<WorkerFactory> */
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

    /**
     * @param  Builder<Worker>  $query
     * @return Builder<Worker>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Worker>  $query
     * @return Builder<Worker>
     */
    public function scopeNamed(Builder $query, string $term): Builder
    {
        return $query->whereRaw("(first_names || ' ' || last_names) ILIKE ?", ['%'.$term.'%']);
    }

    /**
     * @param  Builder<Worker>  $query
     * @return Builder<Worker>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas(
            'contracts',
            fn (Builder $contracts): Builder => $contracts->whereIn(
                'company_id',
                $user->companies()->select('companies.id'),
            ),
        );
    }

    /**
     * @param  Builder<Worker>  $query
     * @return Builder<Worker>
     */
    public function scopeByDocument(Builder $query, string $number, ?int $documentTypeId = null): Builder
    {
        $query->where('document_number', trim($number));

        return $documentTypeId === null
            ? $query
            : $query->where('document_type_id', $documentTypeId);
    }

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * @return HasMany<Contract, $this>
     */
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
