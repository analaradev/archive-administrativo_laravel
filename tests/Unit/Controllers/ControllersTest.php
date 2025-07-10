<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ControllersTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_controller_exists(): void
    {
        $this->assertTrue(class_exists(AuthController::class));
    }

    public function test_dashboard_controller_exists(): void
    {
        $this->assertTrue(class_exists(DashboardController::class));
    }

    public function test_auth_controller_has_required_methods(): void
    {
        $controller = new AuthController();
        
        $this->assertTrue(method_exists($controller, 'showLoginForm'));
        $this->assertTrue(method_exists($controller, 'login'));
        $this->assertTrue(method_exists($controller, 'showRegistrationForm'));
        $this->assertTrue(method_exists($controller, 'register'));
        $this->assertTrue(method_exists($controller, 'logout'));
    }

    public function test_dashboard_controller_has_required_methods(): void
    {
        $controller = new DashboardController();
        
        $this->assertTrue(method_exists($controller, 'index'));
    }

    public function test_dashboard_controller_index_returns_view(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        
        Auth::login($user);
        
        $controller = new DashboardController();
        $response = $controller->index();
        
        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
    }

    public function test_auth_controller_show_login_form_returns_view(): void
    {
        $controller = new AuthController();
        $response = $controller->showLoginForm();
        
        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
    }

    public function test_auth_controller_show_registration_form_returns_view(): void
    {
        $controller = new AuthController();
        $response = $controller->showRegistrationForm();
        
        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
    }
}
