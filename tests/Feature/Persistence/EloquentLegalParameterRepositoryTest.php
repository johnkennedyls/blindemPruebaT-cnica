<?php

namespace Tests\Feature\Persistence;

use App\Domain\Exceptions\ParameterNotConfigured;
use App\Infrastructure\Persistence\Eloquent\EloquentLegalParameterRepository;
use App\Models\LegalParameter;
use Database\Seeders\LegalParameterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentLegalParameterRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LegalParameterSeeder::class);
    }

    public function test_los_divisores_que_usa_el_excel_estan_configurados(): void
    {
        $this->assertSame('360.000000', $this->repository()->value('cesantias_divisor', '2024-06-30'));
        $this->assertSame('360.000000', $this->repository()->value('prima_divisor', '2024-06-30'));
        $this->assertSame('720.000000', $this->repository()->value('vacation_divisor', '2024-06-30'));
    }

    public function test_el_denominador_de_la_prima_no_es_el_de_las_vacaciones(): void
    {
        $this->assertNotSame(
            $this->repository()->value('prima_divisor', '2024-06-30'),
            $this->repository()->value('vacation_divisor', '2024-06-30'),
        );
    }

    public function test_las_tasas_llegan_como_cadena_decimal_y_no_como_flotante(): void
    {
        $this->assertSame('0.120000', $this->repository()->value('cesantias_interest_rate', '2024-06-30'));
        $this->assertSame('0.040000', $this->repository()->value('health_employee_rate', '2024-06-30'));
        $this->assertSame('0.040000', $this->repository()->value('pension_employee_rate', '2024-06-30'));
    }

    public function test_las_dos_literales_del_articulo_sesenta_y_cuatro_estan_configuradas(): void
    {
        $this->assertSame('30.000000', $this->repository()->value('indemnization_first_year_days_low', '2024-06-30'));
        $this->assertSame('20.000000', $this->repository()->value('indemnization_per_year_days_low', '2024-06-30'));
        $this->assertSame('20.000000', $this->repository()->value('indemnization_first_year_days_high', '2024-06-30'));
        $this->assertSame('15.000000', $this->repository()->value('indemnization_per_year_days_high', '2024-06-30'));
        $this->assertSame('10.000000', $this->repository()->value('indemnization_threshold_multiple', '2024-06-30'));
    }

    public function test_una_clave_que_nadie_sembro_avisa_al_administrador(): void
    {
        $this->expectException(ParameterNotConfigured::class);
        $this->expectExceptionMessage('tasa_representacion_legal');

        $this->repository()->value('tasa_representacion_legal', '2024-06-30');
    }

    public function test_un_retiro_anterior_a_la_primera_vigencia_no_inventa_un_parametro(): void
    {
        $this->expectException(ParameterNotConfigured::class);

        $this->repository()->value('cesantias_divisor', '2015-06-30');
    }

    public function test_un_parametro_vencido_no_aplica_en_un_retiro_posterior(): void
    {
        LegalParameter::query()
            ->where('key', 'cesantias_divisor')
            ->update(['effective_to' => '2023-12-31']);

        $this->assertSame('360.000000', $this->repository()->value('cesantias_divisor', '2023-06-30'));

        $this->expectException(ParameterNotConfigured::class);

        $this->repository()->value('cesantias_divisor', '2024-06-30');
    }

    public function test_la_vigencia_mas_reciente_gana_cuando_el_administrador_corrigio_el_valor(): void
    {
        LegalParameter::create([
            'key' => 'cesantias_divisor',
            'numeric_value' => '180',
            'unit' => 'days',
            'effective_from' => '2024-01-01',
            'description' => 'Corrección del administrador a mitad de año.',
        ]);

        $this->assertSame('360.000000', $this->repository()->value('cesantias_divisor', '2023-06-30'));
        $this->assertSame('180.000000', $this->repository()->value('cesantias_divisor', '2024-06-30'));
    }

    public function test_un_valor_de_texto_llega_tal_como_se_diligencio(): void
    {
        LegalParameter::create([
            'key' => 'decreto_salario_minimo',
            'text_value' => 'Decreto 1542 de 2023',
            'unit' => 'amount',
            'effective_from' => '2024-01-01',
        ]);

        $this->assertSame('Decreto 1542 de 2023', $this->repository()->value('decreto_salario_minimo', '2024-06-30'));
    }

    private function repository(): EloquentLegalParameterRepository
    {
        return new EloquentLegalParameterRepository;
    }
}
