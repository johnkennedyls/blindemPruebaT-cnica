<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nit
 * @property string $business_name
 * @property string|null $trade_name
 * @property string|null $activity_code
 * @property string|null $address
 * @property string|null $city
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $legal_rep_name
 * @property string|null $legal_rep_document
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    protected $fillable = [
        'nit',
        'business_name',
        'trade_name',
        'activity_code',
        'address',
        'city',
        'phone',
        'email',
        'legal_rep_name',
        'legal_rep_document',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Company>  $query
     * @return Builder<Company>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Company>  $query
     * @return Builder<Company>
     */
    public function scopeNamed(Builder $query, string $term): Builder
    {
        $like = '%'.$term.'%';

        return $query->where(function (Builder $inner) use ($like) {
            $inner->where('business_name', 'ILIKE', $like)
                ->orWhere('trade_name', 'ILIKE', $like)
                ->orWhere('nit', 'ILIKE', $like);
        });
    }

    /**
     * @return HasMany<Contract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_company')
            ->withPivot('granted_at');
    }

    public function displayName(): string
    {
        return $this->trade_name ?: $this->business_name;
    }

    public function nitLabel(): string
    {
        return "{$this->nit} - {$this->displayName()}";
    }

    public function optionLabel(): string
    {
        return $this->displayName();
    }
}
