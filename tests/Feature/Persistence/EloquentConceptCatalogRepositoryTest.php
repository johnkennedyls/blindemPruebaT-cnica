<?php

namespace Tests\Feature\Persistence;

use App\Domain\Enum\ConceptSide;
use App\Domain\ValueObjects\ConceptDefinition;
use App\Domain\ValueObjects\ConceptList;
use App\Infrastructure\Persistence\Eloquent\EloquentConceptCatalogRepository;
use App\Models\ConceptCatalog;
use Database\Seeders\ConceptCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentConceptCatalogRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ConceptCatalogSeeder::class);
    }

    public function test_el_catalogo_activo_llega_como_lista_del_dominio(): void
    {
        $concepts = $this->repository()->activeConcepts();

        $this->assertInstanceOf(ConceptList::class, $concepts);
        $this->assertCount(ConceptCatalog::query()->active()->count(), $concepts);
        $this->assertGreaterThan(10, $concepts->count());
    }

    public function test_un_concepto_inactivo_no_llega_al_motor(): void
    {
        ConceptCatalog::query()->where('code', 'HORAS_EXTRAS')->update(['is_active' => false]);

        $codes = array_map(
            fn (ConceptDefinition $concept): string => $concept->code,
            $this->repository()->activeConcepts()->inOrder(),
        );

        $this->assertNotContains('HORAS_EXTRAS', $codes);
        $this->assertContains('CESANTIAS', $codes);
    }

    public function test_el_orden_de_la_secuencia_es_el_que_dicen_las_filas_del_excel(): void
    {
        $concepts = $this->repository()->activeConcepts()->inOrder();

        $this->assertSame('CESANTIAS', $concepts[0]->code);
        $this->assertSame('CESANTIAS_INTERES', $concepts[1]->code);
        $this->assertSame('PRIMA', $concepts[2]->code);
        $this->assertSame('VACACIONES', $concepts[3]->code);
        $this->assertSame('DESC_OTRAS', end($concepts)->code);

        $sequences = array_map(fn (ConceptDefinition $concept): int => $concept->sequence, $concepts);
        $sorted = $sequences;
        sort($sorted);

        $this->assertSame($sorted, $sequences);
    }

    public function test_los_renglones_se_reparten_entre_devengados_y_deducciones(): void
    {
        $concepts = $this->repository()->activeConcepts();

        $this->assertNotEmpty($concepts->earning());
        $this->assertNotEmpty($concepts->deductions());

        foreach ($concepts->earning() as $concept) {
            $this->assertSame(ConceptSide::Earning, $concept->side);
        }

        foreach ($concepts->deductions() as $concept) {
            $this->assertSame(ConceptSide::Deduction, $concept->side);
        }
    }

    public function test_los_renglones_manuales_no_tienen_divisor_porque_el_valor_se_diligencia(): void
    {
        $byCode = [];

        foreach ($this->repository()->activeConcepts()->inOrder() as $concept) {
            $byCode[$concept->code] = $concept;
        }

        $this->assertSame('manual', $byCode['DESC_PRESTAMOS']->formulaKey);
        $this->assertNull($byCode['DESC_PRESTAMOS']->defaultDivisor);
        $this->assertSame('manual', $byCode['DESC_OTRAS']->formulaKey);
    }

    public function test_cada_renglon_trae_su_clave_de_formula_para_el_motor(): void
    {
        $keys = array_map(
            fn (ConceptDefinition $concept): string => $concept->formulaKey,
            $this->repository()->activeConcepts()->inOrder(),
        );

        foreach (['cesantias', 'cesantias_interest', 'prima', 'vacation', 'pending_salary', 'transport_auxiliary', 'indemnization', 'health_contribution', 'pension_contribution'] as $key) {
            $this->assertContains($key, $keys);
        }
    }

    private function repository(): EloquentConceptCatalogRepository
    {
        return new EloquentConceptCatalogRepository;
    }
}
