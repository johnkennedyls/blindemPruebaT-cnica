<?php

namespace App\Models;

use App\Domain\Enum\ContractStatus;
use App\Domain\Enum\TransportAuxiliaryMode;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property int $worker_id
 * @property int $company_id
 * @property int $contract_type_id
 * @property int $position_id
 * @property int|null $termination_reason_id
 * @property int $created_by
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property Carbon|null $scheduled_end_date
 * @property Carbon|null $salary_effective_from
 * @property numeric-string $base_salary_monthly
 * @property numeric-string $transport_auxiliary_monthly
 * @property TransportAuxiliaryMode $transport_auxiliary_mode
 * @property numeric-string $overtime_average_monthly
 * @property ContractStatus $status
 * @property int $pending_salary_days
 * @property int $pending_vacation_days
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Contract extends Model
{
    /** @use HasFactory<ContractFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $attributes = [
        'status' => ContractStatus::Activo->value,
        'transport_auxiliary_mode' => TransportAuxiliaryMode::Manual->value,
        'pending_salary_days' => 0,
        'pending_vacation_days' => 0,
    ];

    protected $fillable = [
        'code',
        'worker_id',
        'company_id',
        'contract_type_id',
        'position_id',
        'termination_reason_id',
        'created_by',
        'start_date',
        'end_date',
        'scheduled_end_date',
        'salary_effective_from',
        'base_salary_monthly',
        'transport_auxiliary_monthly',
        'transport_auxiliary_mode',
        'overtime_average_monthly',
        'status',
        'pending_salary_days',
        'pending_vacation_days',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'worker_id' => 'integer',
            'company_id' => 'integer',
            'contract_type_id' => 'integer',
            'position_id' => 'integer',
            'termination_reason_id' => 'integer',
            'created_by' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'scheduled_end_date' => 'date',
            'salary_effective_from' => 'date',
            'base_salary_monthly' => 'decimal:2',
            'transport_auxiliary_monthly' => 'decimal:2',
            'overtime_average_monthly' => 'decimal:2',
            'transport_auxiliary_mode' => TransportAuxiliaryMode::class,
            'status' => ContractStatus::class,
            'pending_salary_days' => 'integer',
            'pending_vacation_days' => 'integer',
        ];
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ContractStatus::Activo);
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    public function scopeByStatus(Builder $query, ?string $status): Builder
    {
        return $query->when($status, fn (Builder $inner) => $inner->where('status', $status));
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    public function scopeOfWorker(Builder $query, ?int $workerId): Builder
    {
        return $query->when($workerId, fn (Builder $inner) => $inner->where('worker_id', $workerId));
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    public function scopeOfCompany(Builder $query, ?int $companyId): Builder
    {
        return $query->when($companyId, fn (Builder $inner) => $inner->where('company_id', $companyId));
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        $companyIds = $user->companies()->pluck('companies.id');

        return $query->where(
            fn (Builder $inner): Builder => $inner
                ->where('created_by', $user->id)
                ->orWhereIn('company_id', $companyIds),
        );
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    public function scopeOfDocument(Builder $query, ?int $documentTypeId, ?string $documentNumber): Builder
    {
        $number = $documentNumber === null ? '' : trim($documentNumber);

        if ($documentTypeId === null && $number === '') {
            return $query;
        }

        return $query->whereHas(
            'worker',
            fn (Builder $inner): Builder => $inner
                ->when($documentTypeId, fn (Builder $byType): Builder => $byType->where('document_type_id', $documentTypeId))
                ->when($number !== '', fn (Builder $byNumber): Builder => $byNumber->where('document_number', $number)),
        );
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    public function scopeCreatedBy(Builder $query, ?int $userId): Builder
    {
        return $query->when($userId, fn (Builder $inner) => $inner->where('created_by', $userId));
    }

    /**
     * @return BelongsTo<Worker, $this>
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<ContractType, $this>
     */
    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * @return BelongsTo<TerminationReason, $this>
     */
    public function terminationReason(): BelongsTo
    {
        return $this->belongsTo(TerminationReason::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Liquidation, $this>
     */
    public function liquidations(): HasMany
    {
        return $this->hasMany(Liquidation::class);
    }

    /**
     * @return HasMany<OvertimeRecord, $this>
     */
    public function overtimeRecords(): HasMany
    {
        return $this->hasMany(OvertimeRecord::class);
    }
}
