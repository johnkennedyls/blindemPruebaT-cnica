<?php

namespace Tests\Feature\Calculation;

use App\Domain\Calculation\LiquidationDraft;
use App\Domain\Calculation\LiquidationEngine;
use App\Domain\Enum\ContractModality;
use App\Domain\Enum\IndemnizationRule;
use App\Domain\Enum\LiquidationVariant;
use App\Domain\Enum\TransportAuxiliaryMode;
use App\Domain\ValueObjects\Money;
use Database\Seeders\ConceptCatalogSeeder;
use Database\Seeders\LegalParameterSeeder;
use Database\Seeders\MinimumWageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiquidacionConformeAlExcelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            MinimumWageSeeder::class,
            LegalParameterSeeder::class,
            ConceptCatalogSeeder::class,
        ]);
    }

    public function test_formato_a_sin_indemnizacion_reproduce_los_doce_numeros_del_excel(): void
    {
        $result = $this->engine()->calculate($this->draft(
            '2023-01-15',
            LiquidationVariant::Prestacional,
            IndemnizationRule::Ninguna,
        ));

        self::assertSame('1075000', $result->amount('cesantias')->amount());
        self::assertSame('64500', $result->amount('cesantias_interest')->amount());
        self::assertSame('1075000', $result->amount('prima')->amount());
        self::assertSame('39602', $result->amount('vacation')->amount());
        self::assertSame('875453', $result->amount('pending_salary')->amount());
        self::assertSame('124548', $result->amount('transport_auxiliary')->amount());
        self::assertSame('0', $result->amount('indemnization')->amount());

        self::assertSame('3254103', $result->earned->amount());
        self::assertSame('36602', $result->amount('health_contribution')->amount());
        self::assertSame('36602', $result->amount('pension_contribution')->amount());
        self::assertSame('73204', $result->deducted->amount());
        self::assertSame('3180899', $result->net->amount());

        self::assertSame('180', (string) $result->context->days->commercialYear);
        self::assertSame('180', (string) $result->context->days->commercialSemester);
        self::assertSame('533', (string) $result->context->days->naturalTotal);
        self::assertSame('2150000', $result->context->bases->prestacional->amount());
        self::assertSame('1900905', $result->context->bases->vacacional->amount());
        self::assertSame('915055', $result->socialSecurityBase->amount());
    }

    public function test_formato_b_con_indemnizacion_reproduce_el_neto_del_excel(): void
    {
        $result = $this->engine()->calculate($this->draft(
            '2021-03-15',
            LiquidationVariant::ConIndemnizacion,
            IndemnizationRule::Art64SegunSalario,
        ));

        self::assertSame(1204, $result->context->days->naturalTotal);
        self::assertSame(3, $result->context->tenure->completeYears);
        self::assertSame(109, $result->context->tenure->fractionDays);

        self::assertSame('1075000', $result->amount('cesantias')->amount());
        self::assertSame('64500', $result->amount('cesantias_interest')->amount());
        self::assertSame('1075000', $result->amount('prima')->amount());
        self::assertSame('39602', $result->amount('vacation')->amount());
        self::assertSame('875453', $result->amount('pending_salary')->amount());
        self::assertSame('124548', $result->amount('transport_auxiliary')->amount());
        self::assertSame('4434027', $result->amount('indemnization')->amount());

        self::assertSame('7688130', $result->earned->amount());
        self::assertSame('73204', $result->deducted->amount());
        self::assertSame('7614926', $result->net->amount());
    }

    public function test_el_orden_de_la_formula_del_auxilio_es_multiplicar_antes_de_dividir(): void
    {
        $result = $this->engine()->calculate($this->draft(
            '2023-01-15',
            LiquidationVariant::Prestacional,
            IndemnizationRule::Ninguna,
        ));

        $auxilio = $result->line('transport_auxiliary');

        self::assertNotNull($auxilio);
        self::assertSame('124548', $auxilio->amount->amount());
        self::assertSame('249095', $auxilio->base->amount());
        self::assertSame(30, $auxilio->divisor);
        self::assertSame('15', $auxilio->quantity);
    }

    public function test_un_salario_por_encima_de_dos_salarios_minimos_avisa_y_deja_el_auxilio_en_cero(): void
    {
        $draft = new LiquidationDraft(
            LiquidationVariant::Prestacional,
            '2023-01-15',
            '2024-06-30',
            Money::of('2700000.00'),
            Money::of('249095.00'),
            Money::of('0'),
            TransportAuxiliaryMode::Manual,
            ContractModality::Indefinido,
            IndemnizationRule::Ninguna,
            vacationDays: 15,
            pendingSalaryDays: 15,
        );

        $result = $this->engine()->calculate($draft);

        self::assertSame('0', $result->amount('transport_auxiliary')->amount());
        self::assertNotEmpty($result->notices);
        self::assertStringContainsString('2 SMLMV', $result->notices[0]);
    }

    private function engine(): LiquidationEngine
    {
        return app(LiquidationEngine::class);
    }

    private function draft(string $startDate, LiquidationVariant $variant, IndemnizationRule $rule): LiquidationDraft
    {
        return new LiquidationDraft(
            $variant,
            $startDate,
            '2024-06-30',
            Money::of('1750905.00'),
            Money::of('249095.00'),
            Money::of('150000.00'),
            TransportAuxiliaryMode::Manual,
            ContractModality::Indefinido,
            $rule,
            vacationDays: 15,
            pendingSalaryDays: 15,
            manualValues: [
                'DESC_PRESTAMOS' => '0',
                'DESC_OTRAS' => '0',
            ],
        );
    }
}
