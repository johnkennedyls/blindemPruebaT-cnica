<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contract;
use App\Models\User;
use Database\Seeders\ContractTypeSeeder;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\PositionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CompanyRegistryTest extends TestCase
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

    public function test_un_invitado_no_llega_al_registro_de_empresas(): void
    {
        $this->get(route('companies.index'))->assertRedirect(route('login'));
        $this->get(route('companies.create'))->assertRedirect(route('login'));
    }

    public function test_el_colaborador_no_puede_alterar_el_registro_de_empresas(): void
    {
        $collaborator = User::factory()->create();
        $company = Company::factory()->create();

        $this->actingAs($collaborator)->get(route('companies.create'))->assertForbidden();
        $this->actingAs($collaborator)->get(route('companies.edit', $company))->assertForbidden();
        $this->actingAs($collaborator)->post(route('companies.store'), $this->payload())->assertForbidden();
        $this->actingAs($collaborator)->put(route('companies.update', $company), $this->payload())->assertForbidden();
        $this->actingAs($collaborator)->delete(route('companies.destroy', $company))->assertForbidden();

        $this->assertDatabaseHas('companies', ['id' => $company->id]);
    }

    public function test_el_administrador_ve_el_listado_y_conta_los_contratos(): void
    {
        $company = Company::factory()->create(['business_name' => 'Comercializadora Andina S.A.S.']);
        Contract::factory()->create(['company_id' => $company->id]);

        $this->actingAs($this->admin())
            ->get(route('companies.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('companies/index')
                ->where('companies.total', 1)
                ->where('companies.data.0.business_name', 'Comercializadora Andina S.A.S.')
                ->where('companies.data.0.contracts_count', 1)
                ->where('auth.is_admin', true),
            );
    }

    public function test_el_colaborador_ve_solo_las_empresas_donde_colabora(): void
    {
        $collaborator = User::factory()->create();
        $mine = Company::factory()->create(['business_name' => 'Comercializadora Andina S.A.S.']);
        $other = Company::factory()->create(['business_name' => 'Transportes del Savar S.A.S.']);

        $collaborator->companies()->attach($mine->id);

        $this->actingAs($collaborator)
            ->get(route('companies.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('companies.total', 1)
                ->where('companies.data.0.id', $mine->id)
                ->where('auth.is_admin', false),
            );

        $this->actingAs($this->admin())
            ->get(route('companies.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('companies.total', 2)
                ->where('companies.data.0.id', $mine->id),
            );
    }

    public function test_el_listado_filtra_por_razon_social_o_nit(): void
    {
        Company::factory()->create(['business_name' => 'Comercializadora Andina S.A.S.', 'nit' => '900111222']);
        Company::factory()->create(['business_name' => 'Transportes del Savar S.A.S.', 'nit' => '900333444']);

        $this->actingAs($this->admin())
            ->get(route('companies.index', ['q' => '900333']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('companies.total', 1)
                ->where('companies.data.0.business_name', 'Transportes del Savar S.A.S.')
                ->where('filters.q', '900333'),
            );
    }

    public function test_el_administrador_registra_una_empresa(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('companies.store'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('companies.edit', Company::query()->firstWhere('nit', '901234567')->id));

        $company = Company::query()->firstWhere('nit', '901234567');

        $this->assertSame('Comercializadora Andina S.A.S.', $company->business_name);
        $this->assertTrue($company->is_active);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'crear',
            'subject_type' => Company::class,
            'subject_id' => $company->id,
        ]);
    }

    public function test_el_nit_duplicado_se_rechaza_con_un_mensaje_en_espanol(): void
    {
        Company::factory()->create(['nit' => '901234567']);

        $this->actingAs($this->admin())
            ->post(route('companies.store'), $this->payload())
            ->assertSessionHasErrors(['nit' => 'Ya existe una empresa registrada con ese NIT.']);

        $this->assertSame(1, Company::query()->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidPayloadProvider')]
    public function test_el_formulario_rechaza_un_nit_mal_formado_o_un_nombre_vacio(array $overrides, string $brokenField): void
    {
        $this->actingAs($this->admin())
            ->post(route('companies.store'), $this->payload($overrides))
            ->assertSessionHasErrors([$brokenField]);

        $this->assertSame(0, Company::query()->count());
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidPayloadProvider(): array
    {
        return [
            'nit con letras' => [['nit' => '901ABC567'], 'nit'],
            'nit con dos guiones' => [['nit' => '901-234-567'], 'nit'],
            'nit demasiado corto' => [['nit' => '1234567'], 'nit'],
            'razon social vacia' => [['business_name' => ''], 'business_name'],
            'correo sin formato' => [['email' => 'andina.com.co'], 'email'],
        ];
    }

    public function test_el_administrador_actualiza_una_empresa_sin_romper_el_nit_propio(): void
    {
        $company = Company::factory()->create(['nit' => '901234567', 'city' => 'Medellín']);

        $this->actingAs($this->admin())
            ->put(route('companies.update', $company), $this->payload(['city' => 'Cali', 'is_active' => false]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('companies.edit', $company));

        $company->refresh();

        $this->assertSame('Cali', $company->city);
        $this->assertFalse($company->is_active);
        $this->assertDatabaseHas('activity_logs', ['action' => 'actualizar', 'subject_id' => $company->id]);
    }

    public function test_no_se_elimina_una_empresa_con_contratos(): void
    {
        $company = Company::factory()->create();
        Contract::factory()->create(['company_id' => $company->id]);

        $this->actingAs($this->admin())
            ->from(route('companies.index'))
            ->delete(route('companies.destroy', $company))
            ->assertRedirect(route('companies.index'));

        $this->assertDatabaseHas('companies', ['id' => $company->id]);
        $this->assertSame('error', $this->toast()['type']);
    }

    public function test_el_administrador_elimina_una_empresa_sin_contratos(): void
    {
        $company = Company::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('companies.destroy', $company))
            ->assertRedirect(route('companies.index'));

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'eliminar', 'subject_id' => $company->id]);
    }

    public function test_el_formulario_de_edicion_carga_la_empresa(): void
    {
        $company = Company::factory()->create(['business_name' => 'Comercializadora Andina S.A.S.']);

        $this->actingAs($this->admin())
            ->get(route('companies.edit', $company))
            ->assertInertia(fn (Assert $page) => $page
                ->component('companies/form')
                ->where('company.id', $company->id)
                ->where('company.business_name', 'Comercializadora Andina S.A.S.')
                ->where('company.contracts_count', 0),
            );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nit' => '901234567',
            'business_name' => 'Comercializadora Andina S.A.S.',
            'trade_name' => 'Andina',
            'activity_code' => '4651',
            'address' => 'Calle 100 # 19-30',
            'city' => 'Bogotá',
            'phone' => '6014567890',
            'email' => 'contacto@andina.com.co',
            'legal_rep_name' => 'Marcela Ruiz Peña',
            'legal_rep_document' => '52145896',
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
