<?php

namespace Tests\Feature;

use App\Domain\Enum\ContractStatus;
use App\Domain\Enum\IndemnizationRule;
use App\Models\Company;
use App\Models\Contract;
use App\Models\Liquidation;
use App\Models\MinimumWage;
use App\Models\User;
use App\Models\Worker;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeedLiquidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_la_semilla_carga_la_demostracion_completa(): void
    {
        $this->assertSame(5, Company::query()->count());
        $this->assertSame(16, Worker::query()->count());
        $this->assertSame(21, Contract::query()->count());
        $this->assertSame(8, MinimumWage::query()->count());
    }

    public function test_hay_un_trabajador_con_dos_contratos_vigentes_en_el_mismo_rango(): void
    {
        $repetidos = Contract::query()
            ->where('status', ContractStatus::Activo)
            ->get()
            ->groupBy('worker_id')
            ->filter(fn ($group): bool => $group->count() > 1);

        $this->assertCount(1, $repetidos);
        $this->assertGreaterThan(1, $repetidos->first()->pluck('company_id')->unique()->count());
    }

    public function test_hay_trabajadores_con_contratos_en_varias_empresas(): void
    {
        $porEmpresa = Contract::query()
            ->select('worker_id')
            ->groupBy('worker_id')
            ->havingRaw('count(distinct company_id) > 1')
            ->pluck('worker_id');

        $this->assertGreaterThanOrEqual(3, $porEmpresa->count());
    }

    public function test_los_salarios_de_la_semilla_cubren_varios_rangos_de_smlmv(): void
    {
        $salarios = Contract::query()
            ->whereIn('status', [ContractStatus::Terminado, ContractStatus::EnLiquidacion])
            ->pluck('base_salary_monthly')
            ->map(fn (string $salary): float => (float) $salary)
            ->sort()
            ->values();

        $this->assertLessThan(1_500_000.0, $salarios->first());
        $this->assertGreaterThan(20_000_000.0, $salarios->last());
        $this->assertGreaterThanOrEqual(9, $salarios->unique()->count());
    }

    public function test_cada_contrato_liquidable_de_la_semilla_se_liquida_sin_errores(): void
    {
        $admin = User::query()->where('email', 'admin@blindem.test')->sole();

        $contratos = Contract::query()
            ->with('terminationReason')
            ->whereIn('status', [ContractStatus::Terminado, ContractStatus::EnLiquidacion])
            ->get();

        $this->assertCount(14, $contratos);

        foreach ($contratos as $contract) {
            $this->actingAs($admin)
                ->post(route('liquidations.store', $contract), [
                    'start_date' => $contract->start_date->toDateString(),
                    'settlement_date' => $contract->end_date->toDateString(),
                    'variant' => $contract->terminationReason->indemnization_rule === IndemnizationRule::Ninguna
                        ? 'prestacional'
                        : 'con_indemnizacion',
                    'vacation_days' => $contract->pending_vacation_days,
                    'pending_salary_days' => $contract->pending_salary_days,
                    'manual_values' => ['DESC_PRESTAMOS' => '0', 'DESC_OTRAS' => '0'],
                ])
                ->assertSessionHasNoErrors();
        }

        $this->assertSame($contratos->count(), Liquidation::query()->count());

        foreach (Liquidation::query()->with('contract')->get() as $liquidation) {
            $this->assertGreaterThan(0, $liquidation->items()->count());
            $this->assertGreaterThan(0, (int) $liquidation->total_days_worked);
            $this->assertEqualsWithDelta(
                (float) $liquidation->total_earned - (float) $liquidation->total_deductions,
                (float) $liquidation->net_total,
                0.01,
            );
            $this->assertSame(
                $liquidation->contract->start_date->toDateString(),
                $liquidation->period_from->toDateString(),
            );
            $this->assertSame(
                $liquidation->contract->end_date->toDateString(),
                $liquidation->settlement_date->toDateString(),
            );
            $this->assertSame(ContractStatus::Liquidado, $liquidation->contract->fresh()->status);
        }
    }
}
