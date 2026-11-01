<?php

namespace App\Models;

use App\Domain\Enum\ContractStatus;
use App\Domain\Enum\TransportAuxiliaryMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use HasFactory, SoftDeletes;

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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ContractStatus::Activo);
    }

    public function scopeByStatus(Builder $query, ?string $status): Builder
    {
        return $query->when($status, fn (Builder $inner) => $inner->where('status', $status));
    }

    public function scopeOfWorker(Builder $query, ?int $workerId): Builder
    {
        return $query->when($workerId, fn (Builder $inner) => $inner->where('worker_id', $workerId));
    }

    public function scopeOfCompany(Builder $query, ?int $companyId): Builder
    {
        return $query->when($companyId, fn (Builder $inner) => $inner->where('company_id', $companyId));
    }

    public function scopeCreatedBy(Builder $query, ?int $userId): Builder
    {
        return $query->when($userId, fn (Builder $inner) => $inner->where('created_by', $userId));
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function terminationReason(): BelongsTo
    {
        return $this->belongsTo(TerminationReason::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function liquidations(): HasMany
    {
        return $this->hasMany(Liquidation::class);
    }

    public function overtimeRecords(): HasMany
    {
        return $this->hasMany(OvertimeRecord::class);
    }
}
