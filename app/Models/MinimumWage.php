<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
