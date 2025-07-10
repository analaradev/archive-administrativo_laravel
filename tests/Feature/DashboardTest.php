<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_screen_can_be_rendered_for_authenticated_users(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Dashboard Administrativo');
        $response->assertSee($user->name);
        $response->assertSee($user->email);
    }

    public function test_dashboard_screen_cannot_be_rendered_for_guest_users(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_dashboard_displays_user_information(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('¡Bienvenido, John Doe!');
        $response->assertSee('john@example.com');
    }

    public function test_dashboard_has_logout_functionality(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Cerrar Sesión');
    }

    public function test_dashboard_displays_stats_section(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Usuarios Registrados');
        $response->assertSee('Sistema Activo');
    }
}
