<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $user_id
 * @property int $company_id
 * @property Carbon|null $granted_at
 */
class UserCompany extends Pivot
{
    protected $table = 'user_company';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'company_id',
        'granted_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'company_id' => 'integer',
            'granted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
