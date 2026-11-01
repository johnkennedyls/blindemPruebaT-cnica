<?php

namespace Tests\Unit\Calculation;

use App\Domain\Calculation\Parameters;
use App\Domain\Exceptions\ParameterNotConfigured;
use App\Domain\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryLegalParameterRepository;

final class ParametersTest extends TestCase
{
    public function test_una_cadena_decimal_de_la_base_se_vuelve_entero_de_dias(): void
    {
        $parameters = $this->parameters(['cesantias_divisor' => '360.000000']);

        $this->assertSame(360, $parameters->days('cesantias_divisor'));
    }

    public function test_los_dias_se_truncan_hacia_abajo_como_hace_el_excel(): void
    {
        $this->assertSame(30, $this->parameters(['x' => '30.9'])->days('x'));
    }

    public function test_una_tasa_llega_como_dinero_y_no_como_flotante(): void
    {
        $rate = $this->parameters(['cesantias_interest_rate' => '0.120000'])->rate('cesantias_interest_rate');

        $this->assertInstanceOf(Money::class, $rate);
        $this->assertSame('0.120000000000', $rate->exactAmount());
    }

    public function test_un_dia_indemnizable_tambien_llega_como_dinero(): void
    {
        $days = $this->parameters(['indemnization_first_year_days_low' => '30'])->money('indemnization_first_year_days_low');

        $this->assertSame('30', $days->displayAmount());
    }

    public function test_un_parametro_de_texto_no_se_cuela_como_numero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no tiene un valor numérico');

        $this->parameters(['decreto' => 'Decreto 1542 de 2023'])->days('decreto');
    }

    public function test_una_clave_que_no_esta_configurada_bloquea_antes_de_calcular(): void
    {
        $this->expectException(ParameterNotConfigured::class);

        $this->parameters([])->rate('indemnization_threshold_multiple');
    }

    public function test_la_fecha_de_vigencia_se_conserva_para_el_snapshot(): void
    {
        $parameters = new Parameters(new InMemoryLegalParameterRepository, '2024-06-30');

        $this->assertSame('2024-06-30', $parameters->asOfDate());
    }

    /**
     * @param  array<string, string>  $values
     */
    private function parameters(array $values): Parameters
    {
        return new Parameters(new InMemoryLegalParameterRepository($values), '2024-06-30');
    }
}
