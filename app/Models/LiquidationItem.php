<?php

namespace App\Models;

use App\Domain\Enum\ConceptSide;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $liquidation_id
 * @property string $concept_code
 * @property string $concept_name
 * @property ConceptSide $side
 * @property numeric-string $calculation_base
 * @property numeric-string $quantity
 * @property numeric-string|null $rate
 * @property int|null $divisor
 * @property string $formula_text
 * @property string|null $calc_trace
 * @property numeric-string $amount
 * @property int $sequence
 * @property bool $applies_ss_contribution
 */
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

    /**
     * @return BelongsTo<Liquidation, $this>
     */
    public function liquidation(): BelongsTo
    {
        return $this->belongsTo(Liquidation::class);
    }
}
