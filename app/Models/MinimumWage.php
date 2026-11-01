<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $year
 * @property numeric-string $smlmv
 * @property numeric-string $transport_auxiliary
 * @property Carbon $effective_from
 * @property Carbon $effective_to
 * @property string|null $source
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class MinimumWage extends Model
{
    protected $fillable = [
        'year',
        'smlmv',
        'transport_auxiliary',
        'effective_from',
        'effective_to',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'smlmv' => 'decimal:2',
            'transport_auxiliary' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }
}
