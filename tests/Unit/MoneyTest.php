<?php

namespace Tests\Unit;

use App\Domain\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_cesantias_del_formato_a(): void
    {
        $cesantias = Money::of('2150000')->times(180)->dividedBy(360)->rounded();

        $this->assertSame('1075000', $cesantias->amount());
    }

    public function test_intereses_sobre_cesantias_del_formato_a(): void
    {
        $intereses = Money::of('1075000')->times(180)->times('0.12')->dividedBy(360)->rounded();

        $this->assertSame('64500', $intereses->amount());
    }

    public function test_vacaciones_del_formato_a_usan_la_base_vacacional(): void
    {
        $vacaciones = Money::of('1900905')->times(15)->dividedBy(720)->rounded();

        $this->assertSame('39602', $vacaciones->amount());
    }

    public function test_salario_pendiente_del_formato_a(): void
    {
        $salario = Money::of('1750905')->times(15)->dividedBy(30)->rounded();

        $this->assertSame('875453', $salario->amount());
    }

    public function test_auxilio_multiplica_antes_de_dividir(): void
    {
        $auxilio = Money::of('249095')->times(15)->dividedBy(30)->rounded();

        $this->assertSame('124548', $auxilio->amount());
    }

    public function test_dividir_antes_de_multiplicar_desvia_un_peso(): void
    {
        $auxilio = Money::of('249095')->dividedBy(30)->times(15)->rounded();

        $this->assertSame('124547', $auxilio->amount());
    }

    public function test_base_de_cotizaciones_es_salario_mas_vacaciones(): void
    {
        $salario = Money::of('1750905')->times(15)->dividedBy(30)->rounded();
        $vacaciones = Money::of('1900905')->times(15)->dividedBy(720)->rounded();

        $this->assertSame('915055', $salario->plus($vacaciones)->amount());
    }

    public function test_aporte_salud_del_formato_a(): void
    {
        $salud = Money::of('915055')->times('0.04')->rounded();

        $this->assertSame('36602', $salud->amount());
    }

    public function test_total_devengado_del_formato_a(): void
    {
        $devengado = Money::zero()
            ->plus(Money::of('1075000'))
            ->plus(Money::of('64500'))
            ->plus(Money::of('1075000'))
            ->plus(Money::of('39602'))
            ->plus(Money::of('875453'))
            ->plus(Money::of('124548'))
            ->plus(Money::zero());

        $this->assertSame('3254103', $devengado->amount());
    }

    public function test_neto_a_recibir_del_formato_a(): void
    {
        $neto = Money::of('3254103')->minus(Money::of('73204'));

        $this->assertSame('3180899', $neto->amount());
    }

    public function test_redondeo_de_la_mitad_hacia_arriba(): void
    {
        $this->assertSame('124548', Money::of('124547.5')->rounded()->amount());
        $this->assertSame('36602', Money::of('36602.2')->rounded()->amount());
        $this->assertSame('2', Money::of('1.5')->rounded()->amount());
    }

    public function test_rechaza_valores_de_punto_flotante(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('punto flotante');

        Money::of(124547.5);
    }

    public function test_rechaza_cadenas_que_no_son_numero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::of('1.075.000');
    }

    public function test_compara_sin_que_importe_la_escala_interna(): void
    {
        $this->assertTrue(Money::of('100')->equals(Money::of('100.4')));
        $this->assertTrue(Money::of('100.51')->atLeast(Money::of('100')));
        $this->assertTrue(Money::of('0')->isZero());
        $this->assertTrue(Money::of('-5')->isNegative());
    }

    public function test_la_cadena_para_la_persona_no_lleva_ceros_de_la_escala(): void
    {
        $this->assertSame('30', Money::of('30')->displayAmount());
        $this->assertSame('2', Money::of('2.000000')->displayAmount());
        $this->assertSame('0', Money::of('0')->displayAmount());
        $this->assertSame('-249095', Money::of('-249095.00')->displayAmount());
    }

    public function test_la_fraccion_de_dias_indemnizables_se_muestra_hasta_la_escala_real(): void
    {
        $fraction = Money::of('109')->times('20')->dividedBy('365');

        $this->assertSame('5.972602739726', $fraction->displayAmount());
        $this->assertSame('6', $fraction->amount());
    }
}
