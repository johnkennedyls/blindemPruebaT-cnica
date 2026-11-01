<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\DocumentType;
use App\Models\Position;
use App\Models\User;
use App\Models\Worker;
use Database\Seeders\ContractTypeSeeder;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\PositionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ValueError;

class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([DocumentTypeSeeder::class, PositionSeeder::class, ContractTypeSeeder::class]);
    }

    public function test_conserva_el_salario_como_cadena_decimal_y_no_como_flotante(): void
    {
        $contract = Contract::factory()->create(['base_salary_monthly' => '2150000.50']);

        $this->assertSame('2150000.50', $contract->fresh()->base_salary_monthly);
    }

    public function test_rechaza_dos_contratos_activos_solapados_del_mismo_trabajador(): void
    {
        $contract = Contract::factory()->create();

        $this->assertRejected(fn () => Contract::factory()->create([
            'worker_id' => $contract->worker_id,
            'company_id' => $contract->company_id,
            'start_date' => '2026-06-01',
        ]), 'ex_contract_no_overlap');
    }

    public function test_autoriza_otro_contrato_cuando_el_solapado_queda_borrado_logicamente(): void
    {
        $contract = Contract::factory()->create();

        $contract->delete();

        $nuevo = Contract::factory()->create([
            'worker_id' => $contract->worker_id,
            'company_id' => $contract->company_id,
            'start_date' => '2026-06-01',
        ]);

        $this->assertNotNull($nuevo->id);
        $this->assertSame(1, Contract::query()->count());
    }

    public function test_rechaza_un_salario_base_en_cero(): void
    {
        $this->assertRejected(
            fn () => Contract::factory()->create(['base_salary_monthly' => '0']),
            'ck_c_salary'
        );
    }

    public function test_rechaza_un_rango_de_fechas_invertido(): void
    {
        $this->assertRejected(
            fn () => Contract::factory()->create(['start_date' => '2026-05-01', 'end_date' => '2026-03-01']),
            'ck_c_dates'
        );
    }

    public function test_rechaza_un_valor_que_no_corresponde_a_ningun_caso_del_enum(): void
    {
        $this->expectException(ValueError::class);

        Worker::factory()->create(['gender' => 'x']);
    }

    public function test_rechaza_un_nit_vacio(): void
    {
        $this->assertRejected(fn () => Company::factory()->create(['nit' => '']), 'ck_company_nit');
    }

    public function test_rechaza_dos_trabajadores_con_el_mismo_documento(): void
    {
        $worker = Worker::factory()->create();

        $this->assertRejected(
            fn () => Worker::factory()->create(['document_number' => $worker->document_number]),
            'uq_worker_doc'
        );
    }

    public function test_rechaza_un_contrato_de_un_trabajador_que_no_existe(): void
    {
        $this->assertRejected(
            fn () => Contract::factory()->create(['worker_id' => 999999]),
            'contracts_worker_id_foreign'
        );
    }

    public function test_rechaza_un_correo_repetido_sin_importar_mayusculas(): void
    {
        User::factory()->create(['email' => 'ana.rodriguez@blindem.test']);

        $this->assertRejected(
            fn () => User::factory()->create(['email' => 'ANA.RODRIGUEZ@BLINDEM.TEST']),
            'uq_users_email_lower'
        );
    }

    public function test_busca_trabajadores_por_un_fragmento_del_nombre(): void
    {
        Worker::factory()->create(['first_names' => 'Ana Maria', 'last_names' => 'Rodriguez Perez']);
        Worker::factory()->create(['first_names' => 'Luis', 'last_names' => 'Gomez']);

        $this->assertSame(1, Worker::query()->named('rodriguez')->count());
        $this->assertSame(1, Worker::query()->named('ana mar')->count());
        $this->assertSame(0, Worker::query()->named('rodriiguez')->count());
    }

    public function test_busca_trabajadores_por_numero_y_por_tipo_de_documento(): void
    {
        $worker = Worker::factory()->create(['document_number' => '1002345678']);

        $this->assertTrue(Worker::query()->byDocument('1002345678')->exists());
        $this->assertTrue(Worker::query()->byDocument(' 1002345678 ')->exists());
        $this->assertTrue(Worker::query()->byDocument('1002345678', $worker->document_type_id)->exists());
        $this->assertFalse(Worker::query()->byDocument('1002345678', $worker->document_type_id + 1)->exists());
    }

    public function test_las_tablas_parametricas_quedan_sembradas_una_sola_vez(): void
    {
        $this->assertSame(5, DocumentType::query()->count());
        $this->assertSame(6, Position::query()->count());
        $this->assertSame(5, ContractType::query()->count());
    }

    private function assertRejected(callable $work, string $needle): void
    {
        try {
            $work();
        } catch (QueryException $e) {
            $this->assertStringContainsString($needle, $e->getMessage());

            return;
        }

        $this->fail("se esperaba que la base rechazara la operacion con {$needle}");
    }
}
