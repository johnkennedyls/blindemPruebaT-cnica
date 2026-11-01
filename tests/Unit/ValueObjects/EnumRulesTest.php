<?php

namespace Tests\Unit\ValueObjects;

use App\Domain\Enum\ConceptSide;
use App\Domain\Enum\ContractModality;
use App\Domain\Enum\ContractStatus;
use App\Domain\Enum\IndemnizationRule;
use App\Domain\Enum\LiquidationStatus;
use App\Domain\Enum\LiquidationVariant;
use App\Domain\Enum\TransportAuxiliaryMode;
use App\Domain\Enum\UserRole;
use App\Domain\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class EnumRulesTest extends TestCase
{
    public function test_solo_la_regla_ninguna_niega_el_derecho_a_indemnizacion(): void
    {
        foreach (IndemnizationRule::cases() as $rule) {
            $this->assertSame(
                $rule !== IndemnizationRule::Ninguna,
                $rule->applies(),
                $rule->value,
            );
        }
    }

    public function test_la_unica_variante_con_renglon_de_indemnizacion_es_la_b(): void
    {
        $this->assertTrue(LiquidationVariant::ConIndemnizacion->includesIndemnization());
        $this->assertFalse(LiquidationVariant::Prestacional->includesIndemnization());
    }

    public function test_el_auxilio_corresponde_en_el_modo_auto_y_en_el_manual(): void
    {
        $this->assertTrue(TransportAuxiliaryMode::Auto->accruesAuxiliary());
        $this->assertTrue(TransportAuxiliaryMode::Manual->accruesAuxiliary());
        $this->assertFalse(TransportAuxiliaryMode::SinDerecho->accruesAuxiliary());
    }

    public function test_el_signo_del_renglon_suma_los_devengados_y_resta_las_deducciones(): void
    {
        $amount = Money::of('36602');

        $this->assertSame('36602', $amount->times(ConceptSide::Earning->sign())->amount());
        $this->assertSame('-36602', $amount->times(ConceptSide::Deduction->sign())->amount());
    }

    public function test_obra_o_labor_se_liquida_como_un_indefinido(): void
    {
        $this->assertTrue(ContractModality::Indefinido->countsAsIndefinidoForIndemnization());
        $this->assertTrue(ContractModality::ObraOLabor->countsAsIndefinidoForIndemnization());
        $this->assertFalse(ContractModality::TerminoFijo->countsAsIndefinidoForIndemnization());
        $this->assertFalse(ContractModality::Aprendizaje->countsAsIndefinidoForIndemnization());
    }

    public function test_solo_un_contrato_terminado_o_en_liquidacion_admite_liquidacion(): void
    {
        $this->assertFalse(ContractStatus::Activo->canBeLiquidated());
        $this->assertTrue(ContractStatus::Terminado->canBeLiquidated());
        $this->assertTrue(ContractStatus::EnLiquidacion->canBeLiquidated());
        $this->assertFalse(ContractStatus::Liquidado->canBeLiquidated());

        $this->assertFalse(ContractStatus::Activo->isClosed());
        $this->assertTrue(ContractStatus::Liquidado->isClosed());
    }

    public function test_una_liquidacion_anulada_no_se_exporta_pero_si_se_consulta(): void
    {
        $this->assertTrue(LiquidationStatus::Calculada->isExportable());
        $this->assertTrue(LiquidationStatus::Aprobada->isExportable());
        $this->assertFalse(LiquidationStatus::Anulada->isExportable());
        $this->assertTrue(LiquidationStatus::Borrador->isEditable());
        $this->assertFalse(LiquidationStatus::Anulada->isEditable());
    }

    public function test_un_colaborador_no_alcanza_los_catalogos(): void
    {
        $this->assertTrue(UserRole::Administrator->isAdmin());
        $this->assertFalse(UserRole::Collaborator->isAdmin());
    }
}
