<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Calculation\Dto\LiquidationContext;
use App\Domain\Calculation\Dto\LiquidationLine;
use App\Domain\Calculation\Dto\LiquidationResult;
use App\Domain\Contracts\LiquidationRepository;
use App\Domain\Enum\BaseKind;
use App\Domain\Enum\ContractStatus;
use App\Domain\Enum\LiquidationStatus;
use App\Domain\ValueObjects\LiquidationReceipt;
use App\Models\Contract;
use App\Models\LegalParameter;
use App\Models\Liquidation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class EloquentLiquidationRepository implements LiquidationRepository
{
    public function save(
        LiquidationResult $result,
        int $contractId,
        int $userId,
        ?string $observations = null,
    ): LiquidationReceipt {
        $context = $result->context;
        $header = $this->header($result, $context, $contractId, $userId, $observations);
        $header['folio'] = $this->nextFolio($context->draft->settlementDate);

        return DB::transaction(function () use ($header, $result, $contractId): LiquidationReceipt {
            $liquidation = Liquidation::create($header);

            foreach ($result->lines as $line) {
                $liquidation->items()->create($this->item($line));
            }

            $this->markContractAsLiquidating($contractId);

            return new LiquidationReceipt($liquidation->id, $liquidation->folio);
        });
    }

    private function markContractAsLiquidating(int $contractId): void
    {
        $contract = Contract::query()->findOrFail($contractId);

        if ($contract->status === ContractStatus::Liquidado) {
            return;
        }

        $contract->update(['status' => ContractStatus::EnLiquidacion]);
    }

    /**
     * @return array<string, mixed>
     */
    private function header(
        LiquidationResult $result,
        LiquidationContext $context,
        int $contractId,
        int $userId,
        ?string $observations,
    ): array {
        return [
            'contract_id' => $contractId,
            'created_by' => $userId,
            'variant' => $context->variant(),
            'status' => LiquidationStatus::Calculada,
            'settlement_date' => $context->draft->settlementDate,
            'period_from' => $context->draft->startDate,
            'period_to' => $context->draft->settlementDate,
            'base_prestacional_snapshot' => $context->bases->prestacional->amount(),
            'base_vacacional_snapshot' => $context->bases->vacacional->amount(),
            'smlmv_snapshot' => $context->wages->minimumWage->amount(),
            'cesantia_days' => $context->days->cesantias,
            'prima_days' => $context->days->prima,
            'vacation_days' => $context->days->vacaciones,
            'pending_salary_days' => $context->days->pendingSalary,
            'total_days_worked' => $context->days->naturalTotal,
            'params_snapshot' => $this->snapshot($context, $result),
            'total_earned' => $result->earned->amount(),
            'total_deductions' => $result->deducted->amount(),
            'net_total' => $result->net->amount(),
            'observations' => $observations,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function item(LiquidationLine $line): array
    {
        return [
            'concept_code' => $line->concept->code,
            'concept_name' => $line->concept->name,
            'side' => $line->concept->side,
            'calculation_base' => $line->base->exactAmount(),
            'quantity' => $line->quantity,
            'rate' => $line->divisor === null && ! $line->isEarning() ? $line->quantity : null,
            'divisor' => $line->divisor,
            'formula_text' => $line->formulaText,
            'calc_trace' => $this->trace($line),
            'amount' => $line->amount->exactAmount(),
            'sequence' => $line->concept->sequence,
            'applies_ss_contribution' => $line->concept->appliesSocialSecurity,
        ];
    }

    private function trace(LiquidationLine $line): string
    {
        if ($line->concept->baseKind === BaseKind::Manual) {
            return sprintf('Valor digitado por el usuario: %s', $line->amount->amount());
        }

        $divisor = $line->divisor;

        return $divisor === null
            ? sprintf('%s × %s = %s', $line->base->displayAmount(), $line->quantity, $line->amount->amount())
            : sprintf('(%s × %s) / %d = %s', $line->base->displayAmount(), $line->quantity, $divisor, $line->amount->amount());
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(LiquidationContext $context, LiquidationResult $result): array
    {
        $date = $context->draft->settlementDate;
        $days = $context->days;

        return [
            'captured_at' => Date::now()->toIso8601String(),
            'settlement_date' => $date,
            'legal_parameters' => $this->parametersInForce($date),
            'minimum_wage' => [
                'year' => $context->wages->year,
                'smlmv' => $context->wages->minimumWage->amount(),
                'transport_auxiliary' => $context->wages->transportAuxiliary->amount(),
            ],
            'bases' => [
                'basico' => $context->bases->basic->amount(),
                'auxilio' => $context->bases->transport->amount(),
                'variables' => $context->bases->variables->amount(),
                'prestacional' => $context->bases->prestacional->amount(),
                'vacacional' => $context->bases->vacacional->amount(),
            ],
            'days' => [
                'ano_comercial' => $days->commercialYear,
                'semestre_comercial' => $days->commercialSemester,
                'naturales' => $days->naturalTotal,
                'cesantias' => $days->cesantias,
                'prima' => $days->prima,
                'vacaciones' => $days->vacaciones,
                'salario_pendiente' => $days->pendingSalary,
            ],
            'antiguedad' => [
                'anos_completos' => $context->tenure->completeYears,
                'fraccion' => $context->tenure->fractionDays,
                'dias_del_ano' => $context->tenure->yearDays,
            ],
            'indemnizacion' => [
                'regla' => $context->strategy->rule()->value,
                'formula' => $context->strategy->formulaText($context),
                'detalle' => $context->strategy->days($context)->detail,
            ],
            'avisos' => $result->notices,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function parametersInForce(string $date): array
    {
        $rows = LegalParameter::query()
            ->whereDate('effective_from', '<=', $date)
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            })
            ->orderBy('key')
            ->orderByDesc('effective_from')
            ->get();

        /** @var array<string, string> $frozen */
        $frozen = [];

        foreach ($rows as $row) {
            if (! array_key_exists($row->key, $frozen)) {
                $frozen[$row->key] = $row->value();
            }
        }

        return $frozen;
    }

    private function nextFolio(string $settlementDate): string
    {
        $prefix = 'LIQ-'.substr($settlementDate, 0, 4).'-';

        $last = Liquidation::query()
            ->where('folio', 'like', $prefix.'%')
            ->orderByDesc('folio')
            ->lockForUpdate()
            ->first();

        $sequence = $last === null ? 1 : (int) substr($last->folio, strlen($prefix)) + 1;

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
