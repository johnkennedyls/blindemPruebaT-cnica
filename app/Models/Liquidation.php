<?php

namespace App\Models;

use App\Domain\Enum\LiquidationStatus;
use App\Domain\Enum\LiquidationVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $folio
 * @property int $contract_id
 * @property int $created_by
 * @property int|null $approved_by
 * @property LiquidationVariant $variant
 * @property LiquidationStatus $status
 * @property Carbon $settlement_date
 * @property Carbon|null $period_from
 * @property Carbon|null $period_to
 * @property numeric-string $base_prestacional_snapshot
 * @property numeric-string $base_vacacional_snapshot
 * @property numeric-string $smlmv_snapshot
 * @property int $cesantia_days
 * @property int $prima_days
 * @property int $vacation_days
 * @property int $pending_salary_days
 * @property int $total_days_worked
 * @property array<string, mixed> $params_snapshot
 * @property numeric-string $total_earned
 * @property numeric-string $total_deductions
 * @property numeric-string $net_total
 * @property string|null $observations
 * @property Carbon|null $voided_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Liquidation extends Model
{
    protected $fillable = [
        'folio',
        'contract_id',
        'created_by',
        'approved_by',
        'variant',
        'status',
        'settlement_date',
        'period_from',
        'period_to',
        'base_prestacional_snapshot',
        'base_vacacional_snapshot',
        'smlmv_snapshot',
        'cesantia_days',
        'prima_days',
        'vacation_days',
        'pending_salary_days',
        'total_days_worked',
        'params_snapshot',
        'total_earned',
        'total_deductions',
        'net_total',
        'observations',
        'voided_at',
    ];

    protected function casts(): array
    {
        return [
            'contract_id' => 'integer',
            'created_by' => 'integer',
            'approved_by' => 'integer',
            'variant' => LiquidationVariant::class,
            'status' => LiquidationStatus::class,
            'settlement_date' => 'date',
            'period_from' => 'date',
            'period_to' => 'date',
            'base_prestacional_snapshot' => 'decimal:2',
            'base_vacacional_snapshot' => 'decimal:2',
            'smlmv_snapshot' => 'decimal:2',
            'cesantia_days' => 'integer',
            'prima_days' => 'integer',
            'vacation_days' => 'integer',
            'pending_salary_days' => 'integer',
            'total_days_worked' => 'integer',
            'params_snapshot' => 'array',
            'total_earned' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_total' => 'decimal:2',
            'voided_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Liquidation>  $query
     * @return Builder<Liquidation>
     */
    public function scopeByStatus(Builder $query, ?string $status): Builder
    {
        return $query->when($status, fn (Builder $inner) => $inner->where('status', $status));
    }

    /**
     * @param  Builder<Liquidation>  $query
     * @return Builder<Liquidation>
     */
    public function scopeOfContract(Builder $query, ?int $contractId): Builder
    {
        return $query->when($contractId, fn (Builder $inner) => $inner->where('contract_id', $contractId));
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return HasMany<LiquidationItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(LiquidationItem::class)->orderBy('sequence');
    }

    /**
     * @return HasMany<LiquidationFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(LiquidationFile::class);
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }
}
