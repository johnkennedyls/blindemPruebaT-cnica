<?php

namespace Tests\Unit\Calculation;

use App\Domain\Calculation\Calculators\IndemnizacionCalculator;
use App\Domain\Calculation\Dto\Computation;
use App\Domain\Calculation\Dto\ComputedLines;
use App\Domain\Calculation\Dto\DayCounts;
use App\Domain\Calculation\Dto\IndemnizableDays;
use App\Domain\Calculation\Dto\LiquidationContext;
use App\Domain\Calculation\Dto\SalaryBases;
use App\Domain\Calculation\Dto\Tenure;
use App\Domain\Calculation\Indemnization\IndefinidoMas10Smlmv;
use App\Domain\Calculation\Indemnization\IndefinidoMenos10Smlmv;
use App\Domain\Calculation\Indemnization\IndemnizationStrategyFactory;
use App\Domain\Calculation\Indemnization\SinIndemnizacion;
use App\Domain\Calculation\Indemnization\TerminoFijoPlazoRestante;
use App\Domain\Calculation\LiquidationDraft;
use App\Domain\Calculation\Parameters;
use App\Domain\Enum\BaseKind;
use App\Domain\Enum\ConceptSide;
use App\Domain\Enum\ContractModality;
use App\Domain\Enum\IndemnizationRule;
use App\Domain\Enum\LiquidationVariant;
use App\Domain\Enum\TransportAuxiliaryMode;
use App\Domain\ValueObjects\ConceptDefinition;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\WageSet;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryLegalParameterRepository;

final class IndemnizacionEstrategiasTest extends TestCase
{
    public function test_la_literal_a_reproduce_los_dias_indemnizables_del_excel(): void
    {
        $context = $this->context(IndemnizationRule::Art64LiteralA, 1204);

        $days = $context->strategy->days($context);

        $this->assertSame('30.000000000000', $days->firstYear->exactAmount());
        $this->assertSame('40.000000000000', $days->followingYears->exactAmount());
        $this->assertSame('5.972602739726', $days->fraction->exactAmount());
        $this->assertSame('75.972602739726', $days->amount());
    }

    public function test_la_literal_a_llega_al_valor_de_indemnizacion_del_excel(): void
    {
        $context = $this->context(IndemnizationRule::Art64LiteralA, 1204);

        $computation = $this->computeIndemnization($context);

        $this->assertSame('4434027', $computation->amount->amount());
        $this->assertSame('(Salario Básico × Días Indemnizables) / 30', $computation->formulaText);
    }

    public function test_la_literal_b_cambia_los_dias_por_salario_mas_alto(): void
    {
        $context = $this->context(IndemnizationRule::Art64LiteralB, 1204, salary: '14000000.00');

        $days = $context->strategy->days($context);

        $this->assertSame('20.000000000000', $days->firstYear->exactAmount());
        $this->assertSame('30.000000000000', $days->followingYears->exactAmount());
        $this->assertSame('4.479452054794', $days->fraction->exactAmount());
        $this->assertSame('54.479452054794', $days->amount());
    }

    #[DataProvider('tenureCases')]
    public function test_la_fraccion_del_primer_anio_no_se_repite_por_los_anos_siguientes(int $totalDays, string $expected): void
    {
        $context = $this->context(IndemnizationRule::Art64LiteralA, $totalDays);

        $this->assertSame($expected, $context->strategy->days($context)->amount());
    }

    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function tenureCases(): array
    {
        return [
            'menos de un año' => [200, '40.958904109589'],
            'un año justo' => [365, '30.000000000000'],
            'un año y un dia' => [366, '30.054794520547'],
            'el caso del excel' => [1204, '75.972602739726'],
        ];
    }

    public function test_los_dias_indemnizables_se_guardan_con_la_fraccion_sin_redondear(): void
    {
        $context = $this->context(IndemnizationRule::Art64LiteralA, 1204);

        $computation = $this->computeIndemnization($context);

        $this->assertSame('75.972602739726', $computation->quantity);
        $this->assertSame(30, $computation->divisor);
    }

    public function test_el_plazo_restante_cuenta_los_dias_que_faltan_para_el_vencimiento(): void
    {
        $context = $this->context(
            IndemnizationRule::PlazoRestante,
            400,
            modality: ContractModality::TerminoFijo,
            remainingTermDays: 180,
        );

        $days = $context->strategy->days($context);

        $this->assertTrue($context->strategy->applies($context));
        $this->assertSame('180.000000000000', $days->total->exactAmount());
        $this->assertSame('0.000000000000', $days->fraction->exactAmount());
        $this->assertStringContainsString('180 días', $days->detail);
    }

    public function test_el_plazo_restante_no_aplica_cuando_el_contrato_ya_vencio(): void
    {
        $context = $this->context(
            IndemnizationRule::PlazoRestante,
            400,
            modality: ContractModality::TerminoFijo,
            remainingTermDays: 0,
        );

        $this->assertFalse($context->strategy->applies($context));
        $this->assertSame('0', $this->computeIndemnization($context)->amount->amount());
    }

    public function test_sin_indemnizacion_devuelve_cero_pero_el_renglon_se_imprime(): void
    {
        $context = $this->context(IndemnizationRule::Ninguna, 533, variant: LiquidationVariant::Prestacional);

        $days = $context->strategy->days($context);
        $computation = $this->computeIndemnization($context);

        $this->assertTrue($days->isZero());
        $this->assertFalse($context->strategy->applies($context));
        $this->assertSame('0', $computation->amount->amount());
        $this->assertSame('No aplica según el motivo de retiro', $computation->formulaText);
    }

    public function test_el_formato_a_no_indemniza_aunque_la_regla_lo_pida(): void
    {
        $context = $this->context(IndemnizationRule::Art64SegunSalario, 533, variant: LiquidationVariant::Prestacional);

        $this->assertInstanceOf(IndefinidoMenos10Smlmv::class, $context->strategy);
        $this->assertSame('0', $this->computeIndemnization($context)->amount->amount());
    }

    #[DataProvider('factoryCases')]
    public function test_la_fabrica_de_estrategias_elige_la_regla_segun_el_salario_y_la_modalidad(
        IndemnizationRule $rule,
        ContractModality $modality,
        string $salary,
        string $expected,
    ): void {
        $strategy = $this->factory()->make(
            $rule,
            $modality,
            Money::of($salary),
            Money::of('1300000.00'),
            $this->parameters(),
        );

        $this->assertInstanceOf($expected, $strategy);
    }

    /**
     * @return array<string, array{0: IndemnizationRule, 1: ContractModality, 2: string, 3: string}>
     */
    public static function factoryCases(): array
    {
        return [
            'por debajo del tope de diez minimos' => [
                IndemnizationRule::Art64SegunSalario, ContractModality::Indefinido, '1750905.00', IndefinidoMenos10Smlmv::class,
            ],
            'exactamente en el tope' => [
                IndemnizationRule::Art64SegunSalario, ContractModality::Indefinido, '13000000.00', IndefinidoMas10Smlmv::class,
            ],
            'por encima del tope' => [
                IndemnizationRule::Art64SegunSalario, ContractModality::Indefinido, '14000000.00', IndefinidoMas10Smlmv::class,
            ],
            'literal escrita a mano' => [
                IndemnizationRule::Art64LiteralB, ContractModality::Indefinido, '1750905.00', IndefinidoMas10Smlmv::class,
            ],
            'termino fijo con regla proporcional' => [
                IndemnizationRule::Art64SegunSalario, ContractModality::TerminoFijo, '1750905.00', TerminoFijoPlazoRestante::class,
            ],
            'motivo que no da derecho' => [
                IndemnizationRule::Ninguna, ContractModality::Indefinido, '14000000.00', SinIndemnizacion::class,
            ],
        ];
    }

    public function test_la_fabrica_de_estrategias_usa_el_salario_minimo_del_anio_del_retiro(): void
    {
        $parameters = $this->parameters();

        $this->assertInstanceOf(
            IndefinidoMenos10Smlmv::class,
            $this->factory()->make(
                IndemnizationRule::Art64SegunSalario,
                ContractModality::Indefinido,
                Money::of('6000000.00'),
                Money::of('1300000.00'),
                $parameters,
            ),
        );

        $this->assertInstanceOf(
            IndefinidoMas10Smlmv::class,
            $this->factory()->make(
                IndemnizationRule::Art64SegunSalario,
                ContractModality::Indefinido,
                Money::of('6000000.00'),
                Money::of('600000.00'),
                $parameters,
            ),
        );
    }

    public function test_el_detalle_explica_de_donde_salieron_los_dias(): void
    {
        $context = $this->context(IndemnizationRule::Art64LiteralA, 1204);

        $this->assertSame(
            '30 días el primer año + 40 por años siguientes + 5.972602739726 por la fracción de 109/365',
            $context->strategy->days($context)->detail,
        );
    }

    public function test_una_indemnizacion_de_un_dia_no_se_confunde_con_cero(): void
    {
        $days = IndemnizableDays::single(Money::of('1'), 'un dia');

        $this->assertFalse($days->isZero());
        $this->assertTrue(IndemnizableDays::zero()->isZero());
    }

    public function test_la_antiguedad_se_mide_con_los_dias_del_anio_del_parametro(): void
    {
        $tenure = Tenure::fromTotalDays(1204, 365);

        $this->assertSame(3, $tenure->completeYears);
        $this->assertSame(109, $tenure->fractionDays);
        $this->assertFalse($tenure->isUnderOneYear());
        $this->assertTrue(Tenure::fromTotalDays(300, 365)->isUnderOneYear());
    }

    private function computeIndemnization(LiquidationContext $context): Computation
    {
        return (new IndemnizacionCalculator)->compute($context, ComputedLines::empty(), $this->definition());
    }

    private function definition(): ConceptDefinition
    {
        return new ConceptDefinition(
            code: 'IND_DESPIDO',
            name: 'Indemnización por despido sin justa causa',
            side: ConceptSide::Earning,
            formulaKey: 'indemnization',
            baseKind: BaseKind::SalarioPendiente,
            defaultDivisor: null,
            appliesSocialSecurity: false,
            sequence: 70,
        );
    }

    private function factory(): IndemnizationStrategyFactory
    {
        return new IndemnizationStrategyFactory;
    }

    private function parameters(): Parameters
    {
        return new Parameters(InMemoryLegalParameterRepository::withStatutoryDefaults(), '2024-06-30');
    }

    private function context(
        IndemnizationRule $rule,
        int $totalDays,
        ContractModality $modality = ContractModality::Indefinido,
        string $salary = '1750905.00',
        LiquidationVariant $variant = LiquidationVariant::ConIndemnizacion,
        int $remainingTermDays = 0,
    ): LiquidationContext {
        $parameters = $this->parameters();
        $basic = Money::of($salary);
        $transport = Money::of('249095.00');
        $variables = Money::of('150000.00');

        $strategy = $this->factory()->make(
            $rule,
            $modality,
            $basic,
            Money::of('1300000.00'),
            $parameters,
        );

        return new LiquidationContext(
            draft: new LiquidationDraft(
                variant: $variant,
                startDate: '2021-03-15',
                settlementDate: '2024-06-30',
                baseSalary: $basic,
                transportAuxiliary: $transport,
                overtimeAverage: $variables,
                transportMode: TransportAuxiliaryMode::Manual,
                modality: $modality,
                indemnizationRule: $rule,
                scheduledEndDate: $remainingTermDays > 0 ? '2024-12-27' : null,
            ),
            bases: new SalaryBases(
                basic: $basic,
                transport: $transport,
                variables: $variables,
                prestacional: $basic->plus($transport)->plus($variables),
                vacacional: $basic->plus($variables),
            ),
            days: new DayCounts($totalDays, 180, $totalDays, 180, 180, 15, 15),
            tenure: Tenure::fromTotalDays($totalDays, $parameters->days('fractional_year_days')),
            wages: new WageSet(2024, Money::of('1300000.00'), Money::of('162000.00')),
            parameters: $parameters,
            strategy: $strategy,
            remainingTermDays: $remainingTermDays,
        );
    }
}
