<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RootRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_raiz_envia_al_login_a_quien_no_ha_iniciado_sesion(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_la_raiz_envia_al_panel_a_quien_ya_tiene_sesion(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    public function test_la_raiz_ya_no_muestra_la_plantilla_del_starter_kit(): void
    {
        $this->followingRedirects()
            ->get('/')
            ->assertOk()
            ->assertDontSee('Laravel Starter Kits', false)
            ->assertSee(config('app.name'), false);
    }
}
