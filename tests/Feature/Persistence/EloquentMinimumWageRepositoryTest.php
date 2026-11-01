<?php

namespace Tests\Feature\Persistence;

use App\Domain\Exceptions\WageNotConfigured;
use App\Domain\ValueObjects\WageSet;
use App\Infrastructure\Persistence\Eloquent\EloquentMinimumWageRepository;
use Database\Seeders\MinimumWageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentMinimumWageRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MinimumWageSeeder::class);
    }

    public function test_el_salario_minimo_de_un_ano_llega_como_objeto_de_valor(): void
    {
        $wages = $this->repository()->forYear(2024);

        $this->assertInstanceOf(WageSet::class, $wages);
        $this->assertSame(2024, $wages->year);
        $this->assertSame('1300000', $wages->minimumWage->amount());
        $this->assertSame('162000', $wages->transportAuxiliary->amount());
    }

    public function test_el_dato_llega_de_la_base_sin_punto_flotante(): void
    {
        $wages = $this->repository()->forYear(2025);

        $this->assertSame('1423500.000000000000', $wages->minimumWage->exactAmount());
        $this->assertSame('200000.000000000000', $wages->transportAuxiliary->exactAmount());
    }

    public function test_la_fecha_del_retiro_elige_la_vigencia_correspondiente(): void
    {
        $this->assertSame('1160000', $this->repository()->forDate('2023-06-30')->minimumWage->amount());
        $this->assertSame('1300000', $this->repository()->forDate('2024-06-30')->minimumWage->amount());
        $this->assertSame('1423500', $this->repository()->forDate('2025-06-30')->minimumWage->amount());
    }

    public function test_los_bordes_de_la_vigencia_se_incluyen(): void
    {
        $this->assertSame('1300000', $this->repository()->forDate('2024-01-01')->minimumWage->amount());
        $this->assertSame('1300000', $this->repository()->forDate('2024-12-31')->minimumWage->amount());
        $this->assertSame('1423500', $this->repository()->forDate('2025-01-01')->minimumWage->amount());
    }

    public function test_un_ano_sin_registro_bloquea_la_liquidacion(): void
    {
        $this->expectException(WageNotConfigured::class);
        $this->expectExceptionMessage('el año 1999');

        $this->repository()->forYear(1999);
    }

    public function test_una_fecha_fuera_de_todas_las_vigencias_no_inventa_un_salario(): void
    {
        $this->expectException(WageNotConfigured::class);
        $this->expectExceptionMessage('2027-06-30');

        $this->repository()->forDate('2027-06-30');
    }

    private function repository(): EloquentMinimumWageRepository
    {
        return new EloquentMinimumWageRepository;
    }
}
