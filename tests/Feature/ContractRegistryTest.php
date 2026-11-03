<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contract;
use App\Models\TerminationReason;
use App\Models\User;
use App\Models\Worker;
use Database\Seeders\ContractTypeSeeder;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\PositionSeeder;
use Database\Seeders\TerminationReasonSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContractRegistryTest extends TestCase
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
        ]);
    }

    public function test_un_invitado_no_llega_a_los_contratos(): void
    {
        $this->get(route('contracts.index'))->assertRedirect(route('login'));
        $this->get(route('contracts.create'))->assertRedirect(route('login'));
    }

    public function test_el_administrador_ve_el_listado_de_contratos(): void
    {
        $contract = $this->contract();

        $this->actingAs($this->admin())
            ->get(route('contracts.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('contracts/index')
                ->where('contracts.total', 1)
                ->where('contracts.data.0.code', $contract->code)
                ->where('contracts.data.0.worker.name', $contract->worker->fullName())
                ->where('auth.is_admin', true),
            );
    }

    public function test_el_listado_filtra_por_estado_y_empresa(): void
    {
        $terminated = $this->contract(['status' => 'terminado']);
        $active = $this->contract();

        $this->actingAs($this->admin())
            ->get(route('contracts.index', ['status' => 'terminado']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('contracts.total', 1)
                ->where('contracts.data.0.id', $terminated->id)
                ->where('filters.status', 'terminado'),
            );

        $this->actingAs($this->admin())
            ->get(route('contracts.index', ['company_id' => $active->company_id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('contracts.total', 1)
                ->where('contracts.data.0.id', $active->id),
            );
    }

    public function test_el_colaborador_puede_registrar_un_contrato_en_su_empresa(): void
    {
        $collaborator = User::factory()->create(['role' => 'collaborator']);
        $company = Company::factory()->create();
        $company->users()->attach($collaborator->id);

        $this->actingAs($collaborator)
            ->post(route('contracts.store'), $this->payload([
                'company_id' => $company->id,
                'worker_id' => Worker::factory()->create()->id,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('contracts.edit', Contract::query()->first()->id));

        $this->assertDatabaseHas('contracts', [
            'company_id' => $company->id,
            'created_by' => $collaborator->id,
            'status' => 'activo',
        ]);
    }

    public function test_el_colaborador_no_puede_registrar_contratos_en_empresas_ajenas(): void
    {
        $collaborator = User::factory()->create(['role' => 'collaborator']);

        $this->actingAs($collaborator)
            ->post(route('contracts.store'), $this->payload([
                'company_id' => Company::factory()->create()->id,
                'worker_id' => Worker::factory()->create()->id,
            ]))
            ->assertSessionHasErrors([
                'company_id' => 'Solo puede registrar contratos para las empresas en las que colabora.',
            ]);
    }

    public function test_un_trabajador_puede_tener_dos_contratos_vigentes_en_el_mismo_rango(): void
    {
        $existing = $this->contract();

        $this->actingAs($this->admin())
            ->post(route('contracts.store'), $this->payload([
                'worker_id' => $existing->worker_id,
                'company_id' => $existing->company_id,
                'start_date' => '2026-03-01',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Contract::query()->where('worker_id', $existing->worker_id)->count());
    }

    public function test_un_contrato_cerrado_exige_fecha_de_retiro_y_causal(): void
    {
        $active = $this->contract();

        $this->actingAs($this->admin())
            ->put(route('contracts.update', $active), array_merge(
                $this->payload([
                    'worker_id' => $active->worker_id,
                    'company_id' => $active->company_id,
                    'contract_type_id' => $active->contract_type_id,
                    'position_id' => $active->position_id,
                    'code' => $active->code,
                ]),
                ['status' => 'terminado'],
            ))
            ->assertSessionHasErrors(['end_date', 'termination_reason_id']);
    }

    public function test_actualizar_un_contrato_con_retiro_calcula_el_cierre(): void
    {
        $reason = TerminationReason::query()->firstWhere('code', 'RET-VOL');
        $contract = $this->contract();

        $this->actingAs($this->admin())
            ->put(route('contracts.update', $contract), array_merge(
                $this->payload([
                    'worker_id' => $contract->worker_id,
                    'company_id' => $contract->company_id,
                    'contract_type_id' => $contract->contract_type_id,
                    'position_id' => $contract->position_id,
                    'code' => $contract->code,
                    'status' => 'terminado',
                    'end_date' => '2026-09-30',
                    'termination_reason_id' => $reason->id,
                ]),
            ))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'status' => 'terminado',
            'termination_reason_id' => $reason->id,
        ]);
    }

    public function test_no_se_puede_eliminar_un_contrato_con_liquidaciones(): void
    {
        $admin = $this->admin();
        $contract = $this->contract(['created_by' => $admin->id, 'company_id' => Company::factory()->create()->id]);
        $contract->company->users()->sync([]);

        DB::table('liquidations')->insert([
            'folio' => 'LIQ-2026-00001',
            'contract_id' => $contract->id,
            'created_by' => $admin->id,
            'variant' => 'prestacional',
            'status' => 'calculada',
            'settlement_date' => '2026-10-31',
            'base_prestacional_snapshot' => 2500000,
            'base_vacacional_snapshot' => 2500000,
            'smlmv_snapshot' => 1750905,
            'cesantia_days' => 180,
            'prima_days' => 90,
            'vacation_days' => 5,
            'pending_salary_days' => 20,
            'total_days_worked' => 300,
            'params_snapshot' => '{}',
            'total_earned' => 100,
            'total_deductions' => 40,
            'net_total' => 60,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('contracts.destroy', $contract))
            ->assertRedirect();

        $this->assertDatabaseHas('contracts', ['id' => $contract->id]);

        $toast = session()->get('inertia.flash_data')['toast'] ?? [];
        $this->assertSame('El contrato '.$contract->code.' ya tiene liquidaciones y no se puede eliminar.', $toast['message'] ?? null);
    }

    public function test_el_contrato_sin_liquidaciones_se_puede_eliminar(): void
    {
        $admin = $this->admin();
        $contract = $this->contract(['created_by' => $admin->id, 'company_id' => Company::factory()->create()->id]);
        $contract->company->users()->sync([$admin->id]);

        $this->actingAs($admin)
            ->delete(route('contracts.destroy', $contract))
            ->assertRedirect(route('contracts.index'));

        $this->assertDatabaseMissing('contracts', ['id' => $contract->id, 'deleted_at' => null]);
    }

    public static function invalidPayloadProvider(): array
    {
        return [
            'salario en cero' => [
                ['base_salary_monthly' => '0'],
                'base_salary_monthly',
            ],
            'auxilio con modo inexistente' => [
                ['transport_auxiliary_mode' => 'otro'],
                'transport_auxiliary_mode',
            ],
            'codigo con espacios' => [
                ['code' => 'CTR 000099'],
                'code',
            ],
            'dias pendientes fuera de rango' => [
                ['pending_salary_days' => '45'],
                'pending_salary_days',
            ],
        ];
    }

    #[DataProvider('invalidPayloadProvider')]
    public function test_el_formulario_rechaza_un_contrato_mal_formado(array $overrides, string $brokenField): void
    {
        $this->actingAs($this->admin())
            ->post(route('contracts.store'), $this->payload($overrides))
            ->assertSessionHasErrors([$brokenField]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function contract(array $overrides = []): Contract
    {
        return Contract::factory()->create($overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'CTR-000099',
            'worker_id' => Worker::factory()->create()->id,
            'company_id' => Company::factory()->create()->id,
            'contract_type_id' => DB::table('contract_types')->orderBy('id')->value('id'),
            'position_id' => DB::table('positions')->orderBy('id')->value('id'),
            'status' => 'activo',
            'start_date' => '2026-01-15',
            'end_date' => null,
            'base_salary_monthly' => '2500000.00',
            'transport_auxiliary_mode' => 'auto',
            'transport_auxiliary_monthly' => '249095.00',
            'overtime_average_monthly' => '0',
            'pending_salary_days' => '0',
            'pending_vacation_days' => '0',
        ], $overrides);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'administrator']);
    }
}
