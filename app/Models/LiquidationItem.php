<?php

namespace App\Models;

use App\Domain\Enum\ConceptSide;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiquidationItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'liquidation_id',
        'concept_code',
        'concept_name',
        'side',
        'calculation_base',
        'quantity',
        'rate',
        'divisor',
        'formula_text',
        'calc_trace',
        'amount',
        'sequence',
        'applies_ss_contribution',
    ];

    protected function casts(): array
    {
        return [
            'liquidation_id' => 'integer',
            'side' => ConceptSide::class,
            'calculation_base' => 'decimal:2',
            'quantity' => 'decimal:4',
            'rate' => 'decimal:5',
            'divisor' => 'integer',
            'amount' => 'decimal:2',
            'sequence' => 'integer',
            'applies_ss_contribution' => 'boolean',
        ];
    }

    public function liquidation(): BelongsTo
    {
        return $this->belongsTo(Liquidation::class);
    }
}
