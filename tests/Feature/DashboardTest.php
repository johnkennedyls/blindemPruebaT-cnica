<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contract;
use App\Models\User;
use Database\Seeders\ContractTypeSeeder;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\PositionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            DocumentTypeSeeder::class,
            PositionSeeder::class,
            ContractTypeSeeder::class,
        ]);
    }

    public function test_un_invitado_es_enviado_al_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_el_panel_se_abre_sin_contratos_registrados(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('summary.active', 0)
                ->where('summary.liquidatable', 0)
                ->where('summary.liquidated', 0)
                ->where('summary.liquidations', 0)
                ->has('pending', 0));
    }

    public function test_el_panel_cuenta_solo_los_contratos_que_el_usuario_ve(): void
    {
        $creator = User::factory()->create();
        $colleague = User::factory()->create();
        $outsider = User::factory()->create();

        $company = Company::factory()->create();
        $company->users()->attach($colleague->id);

        $this->contract(['created_by' => $creator->id, 'company_id' => $company->id]);

        $this->actingAs($creator)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('summary.active', 1));

        $this->actingAs($colleague)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('summary.active', 1));

        $this->actingAs($outsider)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('summary.active', 0));

        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('summary.active', 1));
    }

    public function test_un_contrato_terminado_aparece_como_pendiente_de_liquidar(): void
    {
        $admin = $this->admin();
        $contract = $this->contract([
            'created_by' => $admin->id,
            'status' => 'terminado',
            'end_date' => '2026-06-30',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.active', 0)
                ->where('summary.liquidatable', 1)
                ->has('pending', 1)
                ->where('pending.0.id', $contract->id)
                ->where('pending.0.can_liquidate', true));
    }

    public function test_el_panel_limite_la_lista_de_pendientes_a_cinco_contratos(): void
    {
        $admin = $this->admin();

        for ($i = 1; $i <= 7; $i++) {
            $this->contract([
                'created_by' => $admin->id,
                'status' => 'terminado',
                'start_date' => '2026-0'.($i % 6 + 1).'-01',
                'end_date' => '2026-0'.($i % 6 + 1).'-28',
            ]);
        }

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.liquidatable', 7)
                ->has('pending', 5));
    }

    public function test_el_panel_suma_las_liquidaciones_guardadas(): void
    {
        $admin = $this->admin();
        $contract = $this->contract([
            'created_by' => $admin->id,
            'status' => 'liquidado',
            'end_date' => '2026-06-30',
        ]);

        DB::table('liquidations')->insert([
            'folio' => 'LIQ-2026-00077',
            'contract_id' => $contract->id,
            'created_by' => $admin->id,
            'variant' => 'prestacional',
            'status' => 'calculada',
            'settlement_date' => '2026-06-30',
            'base_prestacional_snapshot' => 2500000,
            'base_vacacional_snapshot' => 2500000,
            'smlmv_snapshot' => 1750905,
            'cesantia_days' => 166,
            'prima_days' => 166,
            'vacation_days' => 5,
            'pending_salary_days' => 0,
            'total_days_worked' => 166,
            'params_snapshot' => '{}',
            'total_earned' => 1000,
            'total_deductions' => 400,
            'net_total' => 600,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.liquidated', 1)
                ->where('summary.liquidations', 1)
                ->has('pending', 0));
    }

    private function contract(array $overrides = []): Contract
    {
        return Contract::factory()->create($overrides);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'administrator']);
    }
}
