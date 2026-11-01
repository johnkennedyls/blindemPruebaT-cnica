<?php

namespace Tests\Feature\Persistence;

use App\Application\LiquidationDraftMapper;
use App\Domain\Calculation\LiquidationEngine;
use App\Domain\Contracts\LiquidationRepository;
use App\Domain\Enum\ActivityAction;
use App\Domain\Enum\LiquidationVariant;
use App\Domain\ValueObjects\LiquidationReceipt;
use App\Infrastructure\Auditing\ActivityLogger;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\LegalParameter;
use App\Models\Liquidation;
use App\Models\LiquidationItem;
use App\Models\TerminationReason;
use App\Models\User;
use App\Models\Worker;
use Database\Seeders\ConceptCatalogSeeder;
use Database\Seeders\ContractTypeSeeder;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\LegalParameterSeeder;
use Database\Seeders\MinimumWageSeeder;
use Database\Seeders\PositionSeeder;
use Database\Seeders\TerminationReasonSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class LiquidationPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private LiquidationEngine $engine;

    private LiquidationDraftMapper $mapper;

    private LiquidationRepository $repository;

    private ActivityLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            DocumentTypeSeeder::class,
            PositionSeeder::class,
            ContractTypeSeeder::class,
            TerminationReasonSeeder::class,
            ConceptCatalogSeeder::class,
            MinimumWageSeeder::class,
            LegalParameterSeeder::class,
        ]);

        $this->engine = app(LiquidationEngine::class);
        $this->mapper = app(LiquidationDraftMapper::class);
        $this->repository = app(LiquidationRepository::class);
        $this->logger = new ActivityLogger;
    }

    public function test_guardar_una_liquidacion_deja_el_folio_y_el_neto_del_excel(): void
    {
        $receipt = $this->save($this->excelContract());

        $liquidation = Liquidation::query()->with('items')->findOrFail($receipt->id);

        $this->assertSame('LIQ-2024-00001', $receipt->folio);
        $this->assertSame('7614926.00', $liquidation->net_total);
        $this->assertSame('7688130.00', $liquidation->total_earned);
        $this->assertSame('73204.00', $liquidation->total_deductions);
        $this->assertSame('2150000.00', $liquidation->base_prestacional_snapshot);
        $this->assertSame('1900905.00', $liquidation->base_vacacional_snapshot);
        $this->assertSame('1300000.00', $liquidation->smlmv_snapshot);
        $this->assertSame(1204, $liquidation->total_days_worked);
        $this->assertSame('2021-03-15', $liquidation->period_from->toDateString());
        $this->assertSame('con_indemnizacion', $liquidation->variant->value);
        $this->assertSame('calculada', $liquidation->status->value);
        $this->assertCount(11, $liquidation->items);
        $this->assertNull($liquidation->items->firstWhere('concept_code', 'HORAS_EXTRAS'));
        $this->assertSame('Liquidación de prueba según el Excel del cliente.', $liquidation->observations);
    }

    public function test_cada_renglon_guardado_conserva_su_base_su_cantidad_y_su_formula(): void
    {
        $items = $this->itemsOf($this->save($this->excelContract())->id);

        $cesantias = $items->firstWhere('concept_code', 'CESANTIAS');
        $vacaciones = $items->firstWhere('concept_code', 'VACACIONES');
        $indemnizacion = $items->firstWhere('concept_code', 'INDEMNIZACION');
        $salud = $items->firstWhere('concept_code', 'DESC_SALUD');

        $this->assertSame('2150000.00', $cesantias->calculation_base);
        $this->assertSame('180.0000', $cesantias->quantity);
        $this->assertSame(360, $cesantias->divisor);
        $this->assertSame('(Base Prestacional × Días) / 360', $cesantias->formula_text);
        $this->assertSame('1075000.00', $cesantias->amount);
        $this->assertSame('(2150000 × 180) / 360 = 1075000', $cesantias->calc_trace);

        $this->assertSame('1900905.00', $vacaciones->calculation_base);
        $this->assertSame(720, $vacaciones->divisor);
        $this->assertSame('39602.00', $vacaciones->amount);

        $this->assertSame('75.9726', $indemnizacion->quantity);
        $this->assertSame('4434027.00', $indemnizacion->amount);

        $this->assertSame('deduction', $salud->side->value);
        $this->assertSame('915055.00', $salud->calculation_base);
        $this->assertSame('0.04000', $salud->rate);
        $this->assertSame('36602.00', $salud->amount);
    }

    public function test_el_renglon_de_vacaciones_es_el_que_acumula_la_cotizacion(): void
    {
        $items = $this->itemsOf($this->save($this->excelContract())->id);

        $accrues = $items->where('applies_ss_contribution', true)->pluck('concept_code')->all();

        $this->assertSame(['VACACIONES', 'SALARIO_PENDIENTE'], $accrues);
    }

    public function test_el_snapshot_congela_parametros_salario_minimo_dias_y_antiguedad(): void
    {
        $liquidation = Liquidation::query()->findOrFail($this->save($this->excelContract())->id);

        $snapshot = $liquidation->params_snapshot;

        $this->assertSame('2024-06-30', $snapshot['settlement_date']);
        $this->assertSame('360.000000', $snapshot['legal_parameters']['cesantias_divisor']);
        $this->assertSame('360.000000', $snapshot['legal_parameters']['prima_divisor']);
        $this->assertSame('0.120000', $snapshot['legal_parameters']['cesantias_interest_rate']);
        $this->assertSame(2024, $snapshot['minimum_wage']['year']);
        $this->assertSame('1300000', $snapshot['minimum_wage']['smlmv']);
        $this->assertSame('162000', $snapshot['minimum_wage']['transport_auxiliary']);
        $this->assertSame(1204, $snapshot['days']['naturales']);
        $this->assertSame(180, $snapshot['days']['cesantias']);
        $this->assertSame(3, $snapshot['antiguedad']['anos_completos']);
        $this->assertSame(109, $snapshot['antiguedad']['fraccion']);
        $this->assertSame('art64_lt_a', $snapshot['indemnizacion']['regla']);
        $this->assertSame('30.000000000000', $snapshot['indemnizacion']['dias']['primer_ano']);
        $this->assertStringContainsString('109/365', $snapshot['indemnizacion']['detalle']);
    }

    public function test_el_snapshot_no_se_mueve_cuando_el_administrador_cambia_un_parametro(): void
    {
        $receipt = $this->save($this->excelContract());

        LegalParameter::query()->where('key', 'cesantias_divisor')->update(['numeric_value' => '300']);

        $snapshot = Liquidation::query()->findOrFail($receipt->id)->params_snapshot;

        $this->assertSame('360.000000', $snapshot['legal_parameters']['cesantias_divisor']);
    }

    public function test_el_folio_avanza_de_uno_por_ano_y_no_se_repite(): void
    {
        $this->assertSame('LIQ-2024-00001', $this->save($this->excelContract())->folio);
        $this->assertSame('LIQ-2024-00002', $this->save($this->excelContract())->folio);
        $this->assertSame('LIQ-2025-00001', $this->save($this->excelContract(), '2025-06-30')->folio);
    }

    public function test_la_auditoria_guarda_el_usuario_la_liquidacion_y_el_neto(): void
    {
        $contract = $this->excelContract();
        $receipt = $this->save($contract);

        $this->logger->log(
            ActivityAction::Liquidar,
            Liquidation::query()->findOrFail($receipt->id),
            'Liquidación '.$receipt->folio.' del contrato '.$contract->code,
            ['net_total' => '7614926.00'],
            $contract->created_by,
        );

        $log = ActivityLog::query()->latest('id')->first();

        $this->assertSame('liquidar', $log->action->value);
        $this->assertSame(Liquidation::class, $log->subject_type);
        $this->assertSame($receipt->id, $log->subject_id);
        $this->assertSame($contract->created_by, $log->user_id);
        $this->assertSame('7614926.00', $log->changes['net_total']);
    }

    public function test_el_formato_a_guardado_deja_el_neto_sin_indemnizacion(): void
    {
        $contract = $this->excelContract();

        $draft = $this->mapper->fromContract(
            contract: $contract,
            settlementDate: '2024-06-30',
            variant: LiquidationVariant::Prestacional,
            vacationDays: 15,
            pendingSalaryDays: 15,
            manualValues: ['DESC_PRESTAMOS' => '0', 'DESC_OTRAS' => '0'],
        );

        $receipt = $this->repository->save($this->engine->calculate($draft), $contract->id, $contract->created_by);

        $liquidation = Liquidation::query()->with('items')->findOrFail($receipt->id);

        $this->assertSame('3180899.00', $liquidation->net_total);
        $this->assertSame('3254103.00', $liquidation->total_earned);
        $this->assertSame('prestacional', $liquidation->variant->value);
        $this->assertSame('0.00', $liquidation->items->firstWhere('concept_code', 'INDEMNIZACION')->amount);
        $this->assertStringContainsString('no aplica', strtolower($liquidation->items->firstWhere('concept_code', 'INDEMNIZACION')->formula_text));
    }

    /**
     * @return Collection<int, LiquidationItem>
     */
    private function itemsOf(int $liquidationId): Collection
    {
        return Liquidation::query()->findOrFail($liquidationId)->items;
    }

    private function save(Contract $contract, string $settlementDate = '2024-06-30'): LiquidationReceipt
    {
        $draft = $this->mapper->fromContract(
            contract: $contract,
            settlementDate: $settlementDate,
            variant: LiquidationVariant::ConIndemnizacion,
            vacationDays: 15,
            pendingSalaryDays: 15,
            manualValues: ['DESC_PRESTAMOS' => '0', 'DESC_OTRAS' => '0'],
        );

        return $this->repository->save(
            $this->engine->calculate($draft),
            $contract->id,
            $contract->created_by,
            'Liquidación de prueba según el Excel del cliente.',
        );
    }

    private function excelContract(): Contract
    {
        $worker = Worker::factory()->create(['document_number' => '109876'.random_int(1000, 9999)]);

        return Contract::factory()->create([
            'worker_id' => $worker->id,
            'company_id' => Company::factory()->create()->id,
            'contract_type_id' => ContractType::query()->where('code', 'INDE')->value('id'),
            'termination_reason_id' => TerminationReason::query()->where('code', 'DES-SJC')->value('id'),
            'created_by' => User::factory()->create()->id,
            'start_date' => '2021-03-15',
            'end_date' => '2024-06-30',
            'base_salary_monthly' => '1750905.00',
            'transport_auxiliary_monthly' => '249095.00',
            'overtime_average_monthly' => '150000.00',
            'status' => 'terminado',
        ]);
    }
}
