<?php

namespace App\Models;

use App\Domain\Enum\OvertimeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRecord extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'contract_id',
        'occurrence_date',
        'type',
        'hours',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'contract_id' => 'integer',
            'occurrence_date' => 'date',
            'type' => OvertimeType::class,
            'hours' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
