<?php

namespace Tests\Unit\Calculation;

use App\Domain\Calculation\WorkedDaysCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WorkedDaysCalculatorTest extends TestCase
{
    #[DataProvider('yearAnchors')]
    public function test_cuenta_dias_comerciales_desde_el_1_de_enero(string $date, int $expected): void
    {
        self::assertSame($expected, WorkedDaysCalculator::commercialFromYearStart($date));
    }

    /**
     * @return array<int, array{0: string, 1: int}>
     */
    public static function yearAnchors(): array
    {
        return [
            ['2024-01-01', 1],
            ['2024-01-31', 31],
            ['2024-06-30', 180],
            ['2024-12-30', 360],
            ['2024-12-31', 361],
        ];
    }

    #[DataProvider('semesterAnchors')]
    public function test_cuenta_dias_comerciales_desde_el_semestre_que_corresponde(string $date, int $expected): void
    {
        self::assertSame($expected, WorkedDaysCalculator::commercialFromSemesterStart($date));
    }

    /**
     * @return array<int, array{0: string, 1: int}>
     */
    public static function semesterAnchors(): array
    {
        return [
            ['2024-01-01', 1],
            ['2024-06-30', 180],
            ['2024-07-01', 1],
            ['2024-07-31', 31],
            ['2024-12-30', 180],
        ];
    }

    public function test_al_cierre_de_junio_el_anclaje_anual_y_el_del_semestre_valen_igual(): void
    {
        self::assertSame(
            WorkedDaysCalculator::commercialFromSemesterStart('2024-06-30'),
            WorkedDaysCalculator::commercialFromYearStart('2024-06-30'),
        );
    }

    public function test_el_anclaje_anual_es_el_defecto_del_excel_y_el_semestre_la_sugerencia(): void
    {
        self::assertSame(360, WorkedDaysCalculator::commercialFromYearStart('2024-12-30'));
        self::assertSame(180, WorkedDaysCalculator::commercialFromSemesterStart('2024-12-30'));
    }

    public function test_los_dias_naturales_incluyen_el_dia_de_ingreso_y_el_de_retiro(): void
    {
        self::assertSame(533, WorkedDaysCalculator::naturalBetween('2023-01-15', '2024-06-30'));
        self::assertSame(1204, WorkedDaysCalculator::naturalBetween('2021-03-15', '2024-06-30'));
        self::assertSame(1, WorkedDaysCalculator::naturalBetween('2024-06-30', '2024-06-30'));
    }

    public function test_un_retiro_el_mismo_dia_del_ingreso_cuenta_un_dia(): void
    {
        self::assertSame(1, WorkedDaysCalculator::naturalBetween('2024-02-29', '2024-02-29'));
    }

    public function test_el_plazo_faltante_se_mide_desde_el_retiro_hasta_el_vencimiento_pactado(): void
    {
        self::assertSame(180, WorkedDaysCalculator::remainingBetween('2024-06-30', '2024-12-27'));
        self::assertSame(0, WorkedDaysCalculator::remainingBetween('2024-06-30', '2024-06-30'));
        self::assertSame(0, WorkedDaysCalculator::remainingBetween('2024-06-30', '2024-01-01'));
    }
}
