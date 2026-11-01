<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contract;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\Worker;
use Database\Seeders\ContractTypeSeeder;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\PositionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WorkerRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            DocumentTypeSeeder::class,
            PositionSeeder::class,
            ContractTypeSeeder::class,
        ]);
    }

    public function test_un_invitado_no_llega_al_registro_de_trabajadores(): void
    {
        $this->get(route('workers.index'))->assertRedirect(route('login'));
        $this->get(route('workers.create'))->assertRedirect(route('login'));
    }

    public function test_el_colaborador_no_puede_alterar_el_registro_de_trabajadores(): void
    {
        $collaborator = User::factory()->create();
        $worker = Worker::factory()->create();

        $this->actingAs($collaborator)->get(route('workers.create'))->assertForbidden();
        $this->actingAs($collaborator)->get(route('workers.edit', $worker))->assertForbidden();
        $this->actingAs($collaborator)->post(route('workers.store'), $this->payload())->assertForbidden();
        $this->actingAs($collaborator)->put(route('workers.update', $worker), $this->payload())->assertForbidden();
        $this->actingAs($collaborator)->delete(route('workers.destroy', $worker))->assertForbidden();

        $this->assertDatabaseHas('workers', ['id' => $worker->id]);
    }

    public function test_el_colaborador_ve_solo_los_trabajadores_de_sus_empresas(): void
    {
        $collaborator = User::factory()->create();
        $company = Company::factory()->create();
        $collaborator->companies()->attach($company->id);

        $mine = Worker::factory()->create(['first_names' => 'Marcela', 'last_names' => 'Ruiz']);
        Worker::factory()->create(['first_names' => 'Ivan', 'last_names' => 'Sandoval']);

        Contract::factory()->create(['worker_id' => $mine->id, 'company_id' => $company->id]);

        $this->actingAs($collaborator)
            ->get(route('workers.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('workers/index')
                ->where('workers.total', 1)
                ->where('workers.data.0.id', $mine->id)
                ->where('auth.is_admin', false),
            );

        $this->actingAs($this->admin())
            ->get(route('workers.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('workers.total', 2)
                ->where('auth.is_admin', true),
            );
    }

    public function test_la_busqueda_cubre_el_nombre_y_el_documento(): void
    {
        Worker::factory()->create([
            'document_number' => '17154488',
            'first_names' => 'Marcela',
            'last_names' => 'Ruiz Peñaranda',
        ]);
        $other = Worker::factory()->create(['first_names' => 'Ivan', 'last_names' => 'Sandoval']);

        $this->actingAs($this->admin())
            ->get(route('workers.index', ['q' => 'ruiz']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('workers.total', 1)
                ->where('workers.data.0.name', 'Marcela Ruiz Peñaranda'),
            );

        $this->actingAs($this->admin())
            ->get(route('workers.index', ['q' => '17154488']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('workers.total', 1)
                ->where('workers.data.0.document_number', '17154488'),
            );

        $this->actingAs($this->admin())
            ->get(route('workers.index', ['q' => 'sandoval']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('workers.total', 1)
                ->where('workers.data.0.id', $other->id),
            );
    }

    public function test_el_administrador_registra_un_trabajador(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('workers.store'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('workers.edit', Worker::query()->firstWhere('document_number', '17154488')->id));

        $worker = Worker::query()->firstWhere('document_number', '17154488');

        $this->assertSame('Marcela Ruiz Peñaranda', $worker->fullName());
        $this->assertSame('f', $worker->gender->value);
        $this->assertSame('1990-05-12', $worker->birth_date->toDateString());
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'crear',
            'subject_type' => Worker::class,
            'subject_id' => $worker->id,
        ]);
    }

    public function test_el_mismo_documento_no_puede_repetirse_con_el_mismo_tipo(): void
    {
        $cc = $this->type('CC');
        Worker::factory()->create([
            'document_type_id' => $cc->id,
            'document_number' => '17154488',
        ]);

        $this->actingAs($this->admin())
            ->post(route('workers.store'), $this->payload())
            ->assertSessionHasErrors([
                'document_number' => 'Ya existe un trabajador registrado con ese tipo y número de documento.',
            ]);

        $this->assertSame(1, Worker::query()->count());
    }

    public function test_el_mismo_numero_si_se_acepta_con_otro_tipo_de_documento(): void
    {
        Worker::factory()->create([
            'document_type_id' => $this->type('CC')->id,
            'document_number' => '17154488',
        ]);

        $this->actingAs($this->admin())
            ->post(route('workers.store'), $this->payload(['document_type_id' => $this->type('CE')->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Worker::query()->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidPayloadProvider')]
    public function test_el_formulario_valida_documento_y_fecha_de_nacimiento(array $overrides, string $brokenField): void
    {
        $this->actingAs($this->admin())
            ->post(route('workers.store'), $this->payload($overrides))
            ->assertSessionHasErrors([$brokenField]);

        $this->assertSame(0, Worker::query()->count());
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidPayloadProvider(): array
    {
        return [
            'documento con letras' => [['document_number' => '1715ABCD'], 'document_number'],
            'documento vacio' => [['document_number' => ''], 'document_number'],
            'nombres vacios' => [['first_names' => ''], 'first_names'],
            'fecha futura' => [['birth_date' => now()->addYear()->toDateString()], 'birth_date'],
            'genero inexistente' => [['gender' => 'x'], 'gender'],
            'sin tipo de documento' => [['document_type_id' => ''], 'document_type_id'],
        ];
    }

    public function test_el_administrador_actualiza_un_trabajador(): void
    {
        $worker = Worker::factory()->create([
            'document_type_id' => $this->type('CC')->id,
            'document_number' => '17154488',
            'first_names' => 'Marcela',
            'last_names' => 'Ruiz',
            'city' => 'Medellín',
        ]);

        $this->actingAs($this->admin())
            ->put(route('workers.update', $worker), $this->payload(['city' => 'Cali', 'is_active' => false]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('workers.edit', $worker));

        $worker->refresh();

        $this->assertSame('Cali', $worker->city);
        $this->assertFalse($worker->is_active);
        $this->assertDatabaseHas('activity_logs', ['action' => 'actualizar', 'subject_id' => $worker->id]);
    }

    public function test_no_se_elimina_un_trabajador_con_contratos(): void
    {
        $worker = Worker::factory()->create();
        Contract::factory()->create(['worker_id' => $worker->id]);

        $this->actingAs($this->admin())
            ->from(route('workers.index'))
            ->delete(route('workers.destroy', $worker))
            ->assertRedirect(route('workers.index'));

        $this->assertDatabaseHas('workers', ['id' => $worker->id]);
        $this->assertSame('error', $this->toast()['type']);
    }

    public function test_el_administrador_elimina_un_trabajador_sin_contratos(): void
    {
        $worker = Worker::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('workers.destroy', $worker))
            ->assertRedirect(route('workers.index'));

        $this->assertDatabaseMissing('workers', ['id' => $worker->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'eliminar', 'subject_id' => $worker->id]);
    }

    public function test_el_formulario_de_edicion_carga_al_trabajador_con_sus_listas(): void
    {
        $worker = Worker::factory()->create([
            'document_type_id' => $this->type('CC')->id,
            'first_names' => 'Marcela',
            'last_names' => 'Ruiz',
        ]);

        $this->actingAs($this->admin())
            ->get(route('workers.edit', $worker))
            ->assertInertia(fn (Assert $page) => $page
                ->component('workers/form')
                ->where('worker.id', $worker->id)
                ->where('worker.name', 'Marcela Ruiz')
                ->where('worker.document_number', $worker->document_number)
                ->has('documentTypes', 4)
                ->has('genders', 3),
            );
    }

    private function type(string $code): DocumentType
    {
        return DocumentType::query()->firstWhere('code', $code);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'document_type_id' => $this->type('CC')->id,
            'document_number' => '17154488',
            'first_names' => 'Marcela',
            'last_names' => 'Ruiz Peñaranda',
            'gender' => 'f',
            'birth_date' => '1990-05-12',
            'email' => 'marcela.ruiz@correo.com',
            'phone' => '3105554433',
            'address' => 'Cra 15 # 98-42',
            'city' => 'Bogotá',
            'department' => 'Cundinamarca',
            'is_active' => true,
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function toast(): array
    {
        return session()->get('inertia.flash_data')['toast'] ?? [];
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'administrator']);
    }
}
