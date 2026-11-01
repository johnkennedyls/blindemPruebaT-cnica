<?php

namespace App\Models;

use App\Domain\Enum\LiquidationFileKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $liquidation_id
 * @property LiquidationFileKind $kind
 * @property string $storage_path
 * @property string $filename
 * @property Carbon|null $generated_at
 */
class LiquidationFile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'liquidation_id',
        'kind',
        'storage_path',
        'filename',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'liquidation_id' => 'integer',
            'kind' => LiquidationFileKind::class,
            'generated_at' => 'datetime',
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
