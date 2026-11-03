<?php

namespace Tests\Feature;

use App\Domain\Enum\ContractStatus;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\DocumentType;
use App\Models\Liquidation;
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
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\TestCase;

class LiquidationFlowTest extends TestCase
{
    use RefreshDatabase;

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
    }

    public function test_un_invitado_no_llega_a_la_pantalla_de_liquidaciones(): void
    {
        $this->get(route('liquidations.index'))->assertRedirect(route('login'));
    }

    public function test_el_colaborador_ve_solo_los_contratos_que_le_corresponden(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $mine = $this->excelContract($owner);
        $this->excelContract($other);
        $liquidation = $this->liquidationOf($mine);

        $this->actingAs($owner)
            ->get(route('liquidations.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('liquidations/index')
                ->where('contracts.total', 1)
                ->where('contracts.data.0.code', $mine->code),
            );

        $this->actingAs($this->admin())
            ->get(route('liquidations.index'))
            ->assertInertia(fn (Assert $page) => $page->where('contracts.total', 2));

        $this->actingAs($other)
            ->get(route('liquidations.show', $liquidation))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('liquidations.show', $liquidation))
            ->assertOk();
    }

    public function test_los_tres_filtros_estrechan_la_lista_de_contratos(): void
    {
        $admin = $this->admin();
        $cedula = DocumentType::query()->where('code', 'CC')->sole();
        $extranjero = DocumentType::query()->where('code', 'CE')->sole();

        $terminado = $this->excelContract($admin, [
            'document_type_id' => $cedula->id,
            'document_number' => '1098760001',
        ]);
        $activo = $this->excelContract($admin, [
            'document_type_id' => $extranjero->id,
            'document_number' => '55443322',
        ]);
        $activo->update(['status' => ContractStatus::Activo]);

        $this->assertFiltered($admin, ['status' => 'terminado'], $terminado);
        $this->assertFiltered($admin, ['document_number' => '1098760001'], $terminado);
        $this->assertFiltered($admin, ['document_type_id' => $extranjero->id], $activo);
        $this->assertFiltered($admin, ['status' => 'activo', 'document_type_id' => $extranjero->id], $activo);
        $this->assertFiltered($admin, ['status' => 'terminado', 'document_type_id' => $cedula->id], $terminado);
        $this->assertFiltered($admin, ['status' => 'liquidado'], null);
        $this->assertFiltered($admin, ['document_number' => '0000000000'], null);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function assertFiltered(User $admin, array $query, ?Contract $expected): void
    {
        $this->actingAs($admin)
            ->get(route('liquidations.index', $query))
            ->assertInertia(function (Assert $page) use ($query, $expected) {
                $page->where('contracts.total', $expected === null ? 0 : 1);

                if ($expected !== null) {
                    $page->where('contracts.data.0.code', $expected->code);
                }

                foreach ($query as $key => $value) {
                    $page->where('filters.'.$key, $value);
                }

                return $page;
            });
    }

    public function test_el_formulario_llega_con_los_campos_que_el_excel_digita_a_mano(): void
    {
        $contract = $this->excelContract();

        $this->actingAs($this->admin())
            ->get(route('liquidations.create', $contract))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('liquidations/create')
                ->has('manualConcepts', 3)
                ->where('suggestedVariant', 'con_indemnizacion')
                ->where('variants', [
                    ['value' => 'prestacional', 'label' => 'Liquidación prestacional'],
                    ['value' => 'con_indemnizacion', 'label' => 'Liquidación con indemnización'],
                ])
                ->where('contract.worker.document', 'CC '.$contract->worker->document_number),
            );
    }

    public function test_guardar_desde_la_pantalla_deja_la_liquidacion_del_excel(): void
    {
        $user = $this->admin();
        $contract = $this->excelContract($user);

        $this->actingAs($user)
            ->post(route('liquidations.store', $contract), $this->payload())
            ->assertSessionHasNoErrors();

        $liquidation = Liquidation::query()->with('items')->sole();

        $this->assertSame('LIQ-2024-00001', $liquidation->folio);
        $this->assertSame('7614926.00', $liquidation->net_total);
        $this->assertSame('7688130.00', $liquidation->total_earned);
        $this->assertCount(11, $liquidation->items);
        $this->assertSame('liquidado', $contract->fresh()->status->value);

        $this->actingAs($user)->get(route('contracts.edit', $contract))->assertForbidden();
        $this->actingAs($user)->post(route('liquidations.store', $contract), $this->payload())->assertForbidden();
        $this->assertSame(1, Liquidation::query()->count());

        $log = ActivityLog::query()->sole();

        $this->assertSame('liquidar', $log->action->value);
        $this->assertSame($user->id, $log->user_id);
        $this->assertStringContainsString($liquidation->folio, $log->description);
    }

    public function test_los_campos_dejados_en_blanco_no_se_confunden_con_un_valor(): void
    {
        $user = $this->admin();
        $contract = $this->excelContract($user);

        $this->actingAs($user)
            ->post(route('liquidations.store', $contract), $this->payload([
                'cesantias_days' => '',
                'prima_days' => '',
                'manual_values' => [
                    'HORAS_EXTRAS' => '',
                    'DESC_PRESTAMOS' => '',
                    'DESC_OTRAS' => '',
                ],
                'observations' => '',
            ]))
            ->assertSessionHasNoErrors();

        $liquidation = Liquidation::query()->with('items')->sole();

        $this->assertSame('7614926.00', $liquidation->net_total);
        $this->assertCount(9, $liquidation->items);
        $this->assertNull($liquidation->observations);
    }

    public function test_la_fecha_no_puede_ser_anterior_al_inicio_del_contrato(): void
    {
        $user = $this->admin();
        $contract = $this->excelContract($user);

        $this->actingAs($user)
            ->post(route('liquidations.store', $contract), $this->payload(['settlement_date' => '2020-01-01']))
            ->assertSessionHasErrors('settlement_date');

        $this->assertSame(0, Liquidation::query()->count());
    }

    public function test_la_fecha_de_ingreso_editable_cambia_el_periodo_sin_tocar_el_contrato(): void
    {
        $user = $this->admin();
        $contract = $this->excelContract($user);

        $this->actingAs($user)
            ->post(route('liquidations.store', $contract), $this->payload(['start_date' => '2022-03-15']))
            ->assertSessionHasNoErrors();

        $liquidation = Liquidation::query()->sole();

        $this->assertSame('2022-03-15', $liquidation->period_from->toDateString());
        $this->assertSame('2024-06-30', $liquidation->period_to->toDateString());
        $this->assertNotSame('7614926.00', $liquidation->net_total);
        $this->assertSame('2021-03-15', $contract->fresh()->start_date->toDateString());
    }

    public function test_omitar_la_fecha_de_ingreso_usa_la_del_contrato(): void
    {
        $user = $this->admin();
        $contract = $this->excelContract($user);

        $this->actingAs($user)
            ->post(route('liquidations.store', $contract), $this->payload(['start_date' => '']))
            ->assertSessionHasNoErrors();

        $this->assertSame('2021-03-15', Liquidation::query()->sole()->period_from->toDateString());
    }

    public function test_los_dias_de_vacaciones_son_obligatorios(): void
    {
        $user = $this->admin();
        $contract = $this->excelContract($user);

        $payload = $this->payload();
        unset($payload['vacation_days']);

        $this->actingAs($user)
            ->post(route('liquidations.store', $contract), $payload)
            ->assertSessionHasErrors('vacation_days');

        $this->assertSame(0, Liquidation::query()->count());
    }

    public function test_el_formato_de_la_liquidacion_debe_ser_uno_de_los_dos_del_excel(): void
    {
        $user = $this->admin();
        $contract = $this->excelContract($user);

        $this->actingAs($user)
            ->post(route('liquidations.store', $contract), $this->payload(['variant' => 'sin_indemnizacion']))
            ->assertSessionHasErrors('variant');
    }

    public function test_un_contrato_activo_no_se_liquida(): void
    {
        $user = $this->admin();
        $contract = $this->excelContract($user);
        $contract->update(['status' => ContractStatus::Activo]);

        $this->actingAs($user)
            ->post(route('liquidations.store', $contract), $this->payload())
            ->assertForbidden();

        $this->assertSame(0, Liquidation::query()->count());
    }

    public function test_la_liquidacion_guardada_se_muestra_con_todos_sus_renglones(): void
    {
        $user = $this->admin();
        $contract = $this->excelContract($user);
        $liquidation = $this->liquidationOf($contract);

        $this->actingAs($user)
            ->get(route('liquidations.show', $liquidation))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('liquidations/show')
                ->where('liquidation.folio', 'LIQ-2024-00001')
                ->where('liquidation.totals.net', '7614926.00')
                ->where('liquidation.bases.prestacional', '2150000.00')
                ->has('liquidation.lines', 11)
                ->where('liquidation.days.worked', 1204)
                ->where('liquidation.indemnization.regla', 'Art. 64 lit. a (30 + 20 días)')
                ->where('contract.code', $contract->code),
            );
    }

    public function test_el_formato_prestacional_guarda_el_neto_sin_indemnizacion(): void
    {
        $user = $this->admin();
        $contract = $this->excelContract($user);

        $this->actingAs($user)
            ->post(route('liquidations.store', $contract), $this->payload(['variant' => 'prestacional']))
            ->assertSessionHasNoErrors();

        $liquidation = Liquidation::query()->sole();

        $this->assertSame('3180899.00', $liquidation->net_total);
        $this->assertSame('prestacional', $liquidation->variant->value);
    }

    public function test_el_excel_descargado_reproduce_los_numeros_del_documento(): void
    {
        $user = $this->admin();
        $contract = $this->excelContract($user);
        $liquidation = $this->liquidationOf($contract);

        $sheet = $this->exportedSheet($liquidation, $user);

        $this->assertSame('LIQUIDACIÓN DE CONTRATO DE TRABAJO E INDEMNIZACIÓN', $sheet->getCell('A1')->getValue());
        $this->assertSame($contract->worker->fullName(), $sheet->getCell('B5')->getValue());
        $this->assertSame(2150000.0, (float) $sheet->getCell('B'.$this->rowOf($sheet, 'Cesantías'))->getValue());
        $this->assertSame(1075000.0, (float) $sheet->getCell('E'.$this->rowOf($sheet, 'Cesantías'))->getValue());
        $this->assertSame('Menor a 10 SMLMV', $sheet->getCell('E'.$this->rowOf($sheet, 'Sueldo Base para Indemnización:'))->getValue());
        $this->assertSame(30.0, (float) $sheet->getCell('B'.$this->rowOf($sheet, 'Días a Indemnizar (1er Año):'))->getValue());
        $this->assertSame(45.972602739726, (float) $sheet->getCell('E'.$this->rowOf($sheet, 'Días a Indemnizar (1er Año):'))->getValue());
        $this->assertSame(4434027.0, (float) $sheet->getCell('E'.$this->rowOf($sheet, 'Total Días a Indemnizar:'))->getValue());
        $this->assertSame(7614926.0, (float) $sheet->getCell('E'.$this->rowOf($sheet, 'NETO TOTAL A RECIBIR (A - B)'))->getCalculatedValue());
    }

    public function test_el_excel_prestacional_omite_el_bloque_de_indemnizacion(): void
    {
        $user = $this->admin();
        $contract = $this->excelContract($user);

        $this->actingAs($user)
            ->post(route('liquidations.store', $contract), $this->payload(['variant' => 'prestacional']));

        $liquidation = Liquidation::query()->sole();
        $sheet = $this->exportedSheet($liquidation, $user);

        $this->assertSame('LIQUIDACIÓN DE CONTRATO DE TRABAJO', $sheet->getCell('A1')->getValue());
        $this->assertStringNotContainsString('INDEMNIZACIÓN POR DESPIDO', (string) $sheet->getCell('A26')->getValue());
        $this->assertSame(3180899.0, (float) $sheet->getCell('E'.$this->rowOf($sheet, 'NETO TOTAL A RECIBIR (A - B)'))->getCalculatedValue());
    }

    public function test_la_vista_previa_devuelve_el_desglose_sin_guardar_nada(): void
    {
        $user = $this->admin();
        $contract = $this->excelContract($user);

        $this->actingAs($user)
            ->postJson(route('liquidations.preview', $contract), $this->payload())
            ->assertOk()
            ->assertJsonPath('totals.earned', '7688130')
            ->assertJsonPath('totals.deducted', '73204')
            ->assertJsonPath('totals.net', '7614926')
            ->assertJsonPath('indemnization_rule', 'Art. 64 lit. a (30 + 20 días)')
            ->assertJsonCount(11, 'lines');

        $this->assertSame(0, Liquidation::query()->count());
    }

    public function test_la_vista_previa_rechaza_datos_fuera_de_rango(): void
    {
        $user = $this->admin();
        $contract = $this->excelContract($user);

        $this->actingAs($user)
            ->postJson(route('liquidations.preview', $contract), $this->payload(['vacation_days' => 900]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('vacation_days');
    }

    public function test_el_excel_incluye_la_hoja_de_instrucciones_y_normativa(): void
    {
        $user = $this->admin();
        $liquidation = $this->liquidationOf($this->excelContract($user));

        $book = $this->exportedBook($liquidation, $user);
        $guide = $book->getSheetByName('Instrucciones y Normativa');

        $this->assertNotNull($guide);
        $this->assertSame('GUÍA DE USO E INSTRUCCIONES DE CÁLCULO', $guide->getCell('A1')->getValue());
        $this->assertStringContainsString('ART. 64 C.S.T.', (string) $guide->getCell('A10')->getValue());
        $this->assertStringContainsString('30 días de salario', (string) $guide->getCell('B11')->getValue());
        $this->assertStringContainsString('/ 360', (string) $guide->getCell('B16')->getValue());
    }

    public function test_un_tercero_no_descarga_el_excel_de_otra_liquidacion(): void
    {
        $owner = $this->admin();
        $liquidation = $this->liquidationOf($this->excelContract($owner));

        $this->actingAs(User::factory()->create())
            ->get(route('liquidations.excel', $liquidation))
            ->assertForbidden();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'administrator']);
    }

    private function exportedSheet(Liquidation $liquidation, User $user): Worksheet
    {
        $response = $this->actingAs($user)->get(route('liquidations.excel', $liquidation));

        $response->assertOk();
        $response->assertDownload($liquidation->folio.'.xlsx');

        return IOFactory::load($response->getFile()->getPathname())->getActiveSheet();
    }

    private function exportedBook(Liquidation $liquidation, User $user): Spreadsheet
    {
        $response = $this->actingAs($user)->get(route('liquidations.excel', $liquidation));

        $response->assertOk();

        return IOFactory::load($response->getFile()->getPathname());
    }

    private function rowOf(Worksheet $sheet, string $label): int
    {
        for ($row = 1; $row <= 80; $row++) {
            if ((string) $sheet->getCell('A'.$row)->getValue() === $label) {
                return $row;
            }
        }

        $this->fail("El Excel exportado no tiene el renglón {$label}.");
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function excelContract(?User $creator = null, array $workerOverrides = []): Contract
    {
        $worker = Worker::factory()->create([
            'document_type_id' => DocumentType::query()->where('code', 'CC')->value('id'),
            ...$workerOverrides,
        ]);

        return Contract::factory()->create([
            'worker_id' => $worker->id,
            'company_id' => Company::factory()->create()->id,
            'contract_type_id' => ContractType::query()->where('code', 'INDE')->value('id'),
            'termination_reason_id' => TerminationReason::query()->where('code', 'DES-SJC')->value('id'),
            'created_by' => ($creator ?? User::factory()->create())->id,
            'start_date' => '2021-03-15',
            'end_date' => '2024-06-30',
            'base_salary_monthly' => '1750905.00',
            'transport_auxiliary_monthly' => '249095.00',
            'overtime_average_monthly' => '150000.00',
            'status' => ContractStatus::Terminado,
            'pending_vacation_days' => 15,
            'pending_salary_days' => 15,
        ]);
    }

    private function liquidationOf(Contract $contract): Liquidation
    {
        $this->actingAs($contract->creator)
            ->post(route('liquidations.store', $contract), $this->payload());

        return Liquidation::query()->with(['items', 'contract.worker', 'creator'])->latest('id')->sole();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'settlement_date' => '2024-06-30',
            'variant' => 'con_indemnizacion',
            'vacation_days' => 15,
            'pending_salary_days' => 15,
            'manual_values' => ['DESC_PRESTAMOS' => '0', 'DESC_OTRAS' => '0'],
            'observations' => 'Liquidación de prueba según el Excel del cliente.',
        ], $overrides);
    }
}
