<?php

namespace Tests\Unit\Calculation;

use App\Domain\Calculation\LiquidationDraft;
use App\Domain\Calculation\Parameters;
use App\Domain\Calculation\SalaryBaseBuilder;
use App\Domain\Enum\ContractModality;
use App\Domain\Enum\IndemnizationRule;
use App\Domain\Enum\LiquidationVariant;
use App\Domain\Enum\TransportAuxiliaryMode;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\WageSet;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryLegalParameterRepository;

final class SalaryBaseBuilderTest extends TestCase
{
    public function test_el_modo_manual_usa_el_auxilio_digitado_como_sale_del_excel(): void
    {
        $assembly = SalaryBaseBuilder::of(
            $this->draft(TransportAuxiliaryMode::Manual, '1750905.00', '249095.00'),
            $this->wages2024(),
            $this->parameters(),
        );

        $this->assertSame('2150000', $assembly->bases->prestacional->amount());
        $this->assertSame('1900905', $assembly->bases->vacacional->amount());
        $this->assertSame('249095', $assembly->bases->transport->amount());
        $this->assertSame([], $assembly->notices);
    }

    public function test_el_modo_auto_toma_el_auxilio_tabulado_del_ano_del_retiro(): void
    {
        $assembly = SalaryBaseBuilder::of(
            $this->draft(TransportAuxiliaryMode::Auto, '1750905.00', '0'),
            $this->wages2024(),
            $this->parameters(),
        );

        $this->assertSame('162000', $assembly->bases->transport->amount());
        $this->assertSame('2062905', $assembly->bases->prestacional->amount());
    }

    public function test_sin_derecho_al_auxilio_deja_la_base_prestacional_igual_a_la_vacacional(): void
    {
        $assembly = SalaryBaseBuilder::of(
            $this->draft(TransportAuxiliaryMode::SinDerecho, '1750905.00', '249095.00'),
            $this->wages2024(),
            $this->parameters(),
        );

        $this->assertTrue($assembly->bases->transport->isZero());
        $this->assertSame('1900905', $assembly->bases->prestacional->amount());
        $this->assertSame(
            $assembly->bases->prestacional->amount(),
            $assembly->bases->vacacional->amount(),
        );
    }

    public function test_un_auxilio_negativo_se_anula_con_su_aviso(): void
    {
        $assembly = SalaryBaseBuilder::of(
            $this->draft(TransportAuxiliaryMode::Manual, '1750905.00', '-249095.00'),
            $this->wages2024(),
            $this->parameters(),
        );

        $this->assertTrue($assembly->bases->transport->isZero());
        $this->assertStringContainsString('negativo', $assembly->notices[0]);
    }

    public function test_un_salario_por_encima_de_dos_salarios_minimos_se_queda_sin_auxilio(): void
    {
        $assembly = SalaryBaseBuilder::of(
            $this->draft(TransportAuxiliaryMode::Manual, '2700000.00', '249095.00'),
            $this->wages2024(),
            $this->parameters(),
        );

        $this->assertTrue($assembly->bases->transport->isZero());
        $this->assertSame('2850000', $assembly->bases->vacacional->amount());
        $this->assertStringContainsString('2 SMLMV del año 2024', $assembly->notices[0]);
        $this->assertStringContainsString('2600000', $assembly->notices[0]);
    }

    public function test_el_limite_de_dos_salarios_minimos_es_inclusive(): void
    {
        $assembly = SalaryBaseBuilder::of(
            $this->draft(TransportAuxiliaryMode::Manual, '2600000.00', '249095.00'),
            $this->wages2024(),
            $this->parameters(),
        );

        $this->assertSame('249095', $assembly->bases->transport->amount());
        $this->assertSame([], $assembly->notices);
    }

    public function test_un_salario_alto_sin_auxilio_no_repite_el_aviso_innecesario(): void
    {
        $assembly = SalaryBaseBuilder::of(
            $this->draft(TransportAuxiliaryMode::SinDerecho, '9000000.00', '249095.00'),
            $this->wages2024(),
            $this->parameters(),
        );

        $this->assertSame([], $assembly->notices);
    }

    public function test_el_tope_se_lee_del_parametro_y_no_de_un_numero_fijo(): void
    {
        $parameters = new Parameters(
            InMemoryLegalParameterRepository::withOverrides(['transport_auxiliary_max_multiple' => '1']),
            '2024-06-30',
        );

        $assembly = SalaryBaseBuilder::of(
            $this->draft(TransportAuxiliaryMode::Manual, '2700000.00', '249095.00'),
            $this->wages2024(),
            $parameters,
        );

        $this->assertTrue($assembly->bases->transport->isZero());
        $this->assertStringContainsString('1 SMLMV', $assembly->notices[0]);
    }

    private function draft(
        TransportAuxiliaryMode $mode,
        string $salary,
        string $transport,
    ): LiquidationDraft {
        return new LiquidationDraft(
            variant: LiquidationVariant::Prestacional,
            startDate: '2023-01-15',
            settlementDate: '2024-06-30',
            baseSalary: Money::of($salary),
            transportAuxiliary: Money::of($transport),
            overtimeAverage: Money::of('150000.00'),
            transportMode: $mode,
            modality: ContractModality::Indefinido,
            indemnizationRule: IndemnizationRule::Ninguna,
        );
    }

    private function wages2024(): WageSet
    {
        return new WageSet(2024, Money::of('1300000.00'), Money::of('162000.00'));
    }

    private function parameters(): Parameters
    {
        return new Parameters(InMemoryLegalParameterRepository::withStatutoryDefaults(), '2024-06-30');
    }
}
