<?php

namespace Tests\Unit\ValueObjects;

use App\Domain\Enum\BaseKind;
use App\Domain\Enum\ConceptSide;
use App\Domain\ValueObjects\ConceptDefinition;
use App\Domain\ValueObjects\ConceptList;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ConceptListTest extends TestCase
{
    public function test_la_lista_acepta_solamente_definiciones_de_concepto(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ConceptList::fromArray([$this->concept('CESANTIAS', 'cesantias', 10), 'CESANTIAS']);
    }

    public function test_una_lista_vacia_no_aporta_renglones(): void
    {
        $this->assertCount(0, ConceptList::empty());
        $this->assertSame([], ConceptList::empty()->inOrder());
    }

    public function test_el_orden_de_la_secuencia_manda_sobre_el_orden_de_las_filas(): void
    {
        $concepts = ConceptList::fromArray([
            $this->concept('DESC_SALUD', 'health_contribution', 90, ConceptSide::Deduction),
            $this->concept('PRIMA', 'prima', 30),
            $this->concept('CESANTIAS', 'cesantias', 10),
        ]);

        $codes = array_map(fn (ConceptDefinition $c): string => $c->code, $concepts->inOrder());

        $this->assertSame(['CESANTIAS', 'PRIMA', 'DESC_SALUD'], $codes);
    }

    public function test_los_devengados_y_las_deducciones_no_se_mezclan(): void
    {
        $concepts = ConceptList::fromArray([
            $this->concept('CESANTIAS', 'cesantias', 10),
            $this->concept('DESC_SALUD', 'health_contribution', 90, ConceptSide::Deduction),
            $this->concept('DESC_PENSION', 'pension_contribution', 100, ConceptSide::Deduction),
        ]);

        $this->assertCount(1, $concepts->earning());
        $this->assertCount(2, $concepts->deductions());
        $this->assertSame('CESANTIAS', $concepts->earning()[0]->code);
        $this->assertSame('DESC_PENSION', $concepts->deductions()[1]->code);
    }

    public function test_los_indices_quedan_correlativos_aunque_se_filtre_la_lista(): void
    {
        $concepts = ConceptList::fromArray([
            $this->concept('DESC_SALUD', 'health_contribution', 90, ConceptSide::Deduction),
            $this->concept('CESANTIAS', 'cesantias', 10),
        ]);

        $this->assertSame([0, 1], array_keys($concepts->inOrder()));
        $this->assertSame([0], array_keys($concepts->earning()));
    }

    public function test_imprimir_la_lista_no_reordena_la_que_recibe_el_motor(): void
    {
        $concepts = ConceptList::fromArray([
            $this->concept('PRIMA', 'prima', 30),
            $this->concept('CESANTIAS', 'cesantias', 10),
        ]);

        $first = $concepts->inOrder()[0]->code;
        $second = $concepts->inOrder()[0]->code;

        $this->assertSame($first, $second);
        $this->assertSame('CESANTIAS', $first);
    }

    private function concept(string $code, string $formulaKey, int $sequence, ConceptSide $side = ConceptSide::Earning): ConceptDefinition
    {
        return new ConceptDefinition(
            $code,
            $code,
            $side,
            $formulaKey,
            BaseKind::Prestacional,
            360,
            false,
            $sequence,
        );
    }
}
