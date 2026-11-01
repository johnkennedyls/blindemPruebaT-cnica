<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\DocumentType;
use App\Models\MinimumWage;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\ContractTypeSeeder;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\MinimumWageSeeder;
use Database\Seeders\PositionSeeder;
use Database\Seeders\TerminationReasonSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ParametricRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            DocumentTypeSeeder::class,
            PositionSeeder::class,
            ContractTypeSeeder::class,
            TerminationReasonSeeder::class,
            MinimumWageSeeder::class,
        ]);
    }

    public function test_un_invitado_no_llega_a_los_parametros(): void
    {
        $this->get(route('parametrics.index', ['resource' => 'cargos']))
            ->assertRedirect(route('login'));
    }

    public function test_el_colaborador_no_puede_gestionar_parametricas(): void
    {
        $collaborator = User::factory()->create(['role' => 'collaborator']);
        $position = Position::query()->first();

        $this->actingAs($collaborator)
            ->get(route('parametrics.index', ['resource' => 'cargos']))
            ->assertForbidden();
        $this->actingAs($collaborator)
            ->get(route('parametrics.create', ['resource' => 'cargos']))
            ->assertForbidden();
        $this->actingAs($collaborator)
            ->get(
                route('parametrics.edit', ['resource' => 'cargos', 'parametric' => $position->id]),
            )->assertForbidden();
        $this->actingAs($collaborator)
            ->post(route('parametrics.store', ['resource' => 'cargos']), $this->positionPayload())
            ->assertForbidden();
        $this->actingAs($collaborator)
            ->put(
                route('parametrics.update', ['resource' => 'cargos', 'parametric' => $position->id]),
                $this->positionPayload(),
            )->assertForbidden();
        $this->actingAs($collaborator)
            ->delete(
                route('parametrics.destroy', ['resource' => 'cargos', 'parametric' => $position->id]),
            )
            ->assertForbidden();
    }

    public function test_un_recurso_desconocido_responde_404(): void
    {
        $this->actingAs($this->admin())
            ->get(route('parametrics.index', ['resource' => 'conceptos']))
            ->assertNotFound();
    }

    public function test_el_administrador_ve_el_catalogo_de_tipos_de_documento(): void
    {
        $this->actingAs($this->admin())
            ->get(route('parametrics.index', ['resource' => 'tipos-de-documento']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('parametrics/index')
                ->where('definition.slug', 'tipos-de-documento')
                ->where('definition.fields.0.name', 'code')
                ->where('rows.data.0.headline', 'CC'),
            );
    }

    public function test_el_administrador_registra_un_cargo(): void
    {
        $this->actingAs($this->admin())
            ->post(route('parametrics.store', ['resource' => 'cargos']), $this->positionPayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('parametrics.edit', [
                'resource' => 'cargos',
                'parametric' => Position::query()->firstWhere('code', 'ALM-01')->id,
            ]));

        $this->assertDatabaseHas('positions', [
            'code' => 'ALM-01',
            'name' => 'Coordinador de bodega',
            'is_active' => true,
        ]);
    }

    public function test_el_codigo_duplicado_se_rechaza_con_mensaje_en_espanol(): void
    {
        $this->actingAs($this->admin())
            ->post(route('parametrics.store', ['resource' => 'cargos']), $this->positionPayload([
                'code' => Position::query()->first()->code,
            ]))
            ->assertSessionHasErrors(['code' => 'Ya existe un cargo con ese código.']);
    }

    public function test_actualiza_un_tipo_de_documento(): void
    {
        $documentType = DocumentType::query()->firstWhere('code', 'CC');

        $this->actingAs($this->admin())
            ->put(route('parametrics.update', [
                'resource' => 'tipos-de-documento',
                'parametric' => $documentType->id,
            ]), [
                'code' => 'CC',
                'name' => 'Cédula de ciudadanía',
                'abbreviation' => 'CC',
                'holder_type' => 'natural',
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('document_types', [
            'id' => $documentType->id,
            'name' => 'Cédula de ciudadanía',
        ]);
    }

    public function test_registra_un_tipo_de_contrato_con_su_regla_de_indemnizacion(): void
    {
        $this->actingAs($this->admin())
            ->post(route('parametrics.store', ['resource' => 'tipos-de-contrato']), [
                'code' => 'OCAS',
                'name' => 'Contrato ocasional',
                'modality' => 'ocasional',
                'statutory_max_months' => null,
                'requires_scheduled_end' => false,
                'indemnization_rule' => 'ninguna',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contract_types', [
            'code' => 'OCAS',
            'modality' => 'ocasional',
            'indemnization_rule' => 'ninguna',
            'applies_indemnization' => false,
        ]);
    }

    public function test_registra_un_motivo_de_retiro(): void
    {
        $this->actingAs($this->admin())
            ->post(route('parametrics.store', ['resource' => 'motivos-de-retiro']), [
                'code' => 'DES-OTRO',
                'description' => 'Despido con motivo determinado',
                'classification' => 'justa_causa',
                'indemnization_rule' => 'ninguna',
                'applies_sanction_art65' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('termination_reasons', [
            'code' => 'DES-OTRO',
            'applies_sanction_art65' => true,
            'applies_indemnization' => false,
        ]);
    }

    public function test_registra_el_smlmv_de_un_anio(): void
    {
        $this->actingAs($this->admin())
            ->post(route('parametrics.store', ['resource' => 'salarios-minimos']), [
                'year' => '2027',
                'smlmv' => '1850000',
                'transport_auxiliary' => '260000',
                'source' => 'Decreto proyectado',
            ])
            ->assertSessionHasNoErrors();

        $wage = MinimumWage::query()->firstWhere('year', 2027);

        $this->assertSame('2027-01-01', $wage->effective_from->toDateString());
        $this->assertSame('2027-12-31', $wage->effective_to->toDateString());
    }

    public function test_no_se_puede_duplicar_el_anio_del_smlmv(): void
    {
        $year = MinimumWage::query()->orderByDesc('year')->value('year');

        $this->actingAs($this->admin())
            ->post(route('parametrics.store', ['resource' => 'salarios-minimos']), [
                'year' => (string) $year,
                'smlmv' => '1900000',
                'transport_auxiliary' => '270000',
            ])
            ->assertSessionHasErrors(['year' => 'Ya existe un salario mínimo con ese año.']);
    }

    public function test_no_se_puede_eliminar_un_cargo_usado_por_contratos(): void
    {
        $contract = Contract::factory()->create();
        $position = $contract->position;

        $this->actingAs($this->admin())
            ->delete(route('parametrics.destroy', [
                'resource' => 'cargos',
                'parametric' => $position->id,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('positions', ['id' => $position->id]);

        $toast = session()->get('inertia.flash_data')['toast'] ?? [];
        $this->assertSame(
            'No se puede eliminar: hay registros que usan este cargo. Desactívelo en su lugar.',
            $toast['message'] ?? null,
        );
    }

    public function test_el_administrador_ve_los_hallazgos_de_busqueda(): void
    {
        $position = Position::query()->first();

        $this->actingAs($this->admin())
            ->get(route('parametrics.index', ['resource' => 'cargos', 'q' => $position->code]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('rows.total', 1)
                ->where('rows.data.0.headline', $position->code),
            );
    }

    public function test_registrar_un_document_type_con_titular_invalido_falla(): void
    {
        $this->actingAs($this->admin())
            ->post(route('parametrics.store', ['resource' => 'tipos-de-documento']), [
                'code' => 'RC',
                'name' => 'Registro civil',
                'abbreviation' => 'RC',
                'holder_type' => 'desconocido',
            ])
            ->assertSessionHasErrors(['holder_type']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function positionPayload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'ALM-01',
            'name' => 'Coordinador de bodega',
            'area' => 'Logística',
            'level' => 'Operativo',
        ], $overrides);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'administrator']);
    }
}
