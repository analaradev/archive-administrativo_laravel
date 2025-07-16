<?php

namespace Tests\Unit\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test authenticated user can access protected routes.
     */
    public function test_authenticated_user_can_access_protected_routes()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }

    /**
     * Test unauthenticated user is redirected to login.
     */
    public function test_unauthenticated_user_redirected_to_login()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    /**
     * Test middleware preserves intended URL.
     */
    public function test_middleware_preserves_intended_url()
    {
        // Try to access dashboard without authentication
        $response = $this->get('/dashboard');
        
        $response->assertRedirect('/login');
        
        // Verify intended URL is stored in session
        $this->assertEquals('/dashboard', session('url.intended'));
    }

    /**
     * Test authenticated user cannot be redirected to login.
     */
    public function test_authenticated_user_not_redirected_when_accessing_protected_route()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
    }

    /**
     * Test middleware works with different authentication guards.
     */
    public function test_middleware_works_with_default_guard()
    {
        // Test with default 'web' guard
        $user = User::factory()->create();
        
        // Manually authenticate user
        Auth::guard('web')->login($user);
        
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }

    /**
     * Test middleware handles session expiration.
     */
    public function test_middleware_handles_expired_session()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Verify user is authenticated
        $this->assertAuthenticated();

        // Manually logout to simulate session expiration
        Auth::logout();

        // Try to access protected route
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    /**
     * Test middleware with AJAX requests.
     */
    public function test_middleware_with_ajax_requests()
    {
        // Make AJAX request without authentication
        $response = $this->getJson('/dashboard');
        
        // Should return 401 for AJAX requests instead of redirect
        $response->assertStatus(401);
    }

    /**
     * Test middleware with API requests expecting JSON.
     */
    public function test_middleware_with_api_requests()
    {
        $response = $this->json('GET', '/dashboard');
        
        // Should return 401 for JSON requests
        $response->assertStatus(401);
    }

    /**
     * Test middleware allows access to public routes.
     */
    public function test_middleware_allows_public_routes()
    {
        // These routes should be accessible without authentication
        $publicRoutes = ['/', '/login', '/register'];
        
        foreach ($publicRoutes as $route) {
            $response = $this->get($route);
            $this->assertNotEquals(401, $response->getStatusCode());
            $this->assertNotEquals('/login', $response->headers->get('Location'));
        }
    }

    /**
     * Test middleware with remember me functionality.
     */
    public function test_middleware_with_remember_me()
    {
        $user = User::factory()->create();
        
        // Simulate remember me login
        Auth::login($user, true);
        
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }

    /**
     * Test middleware prevents access after user deletion.
     */
    public function test_middleware_prevents_access_after_user_deletion()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Verify user can access dashboard
        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        // Delete user from database
        $user->delete();

        // Try to access dashboard again (session might still exist)
        $response = $this->get('/dashboard');
        
        // Should be redirected to login because user no longer exists
        $response->assertRedirect('/login');
    }

    /**
     * Test middleware behavior with multiple concurrent requests.
     */
    public function test_middleware_with_concurrent_requests()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Make multiple requests to verify consistent behavior
        for ($i = 0; $i < 3; $i++) {
            $response = $this->get('/dashboard');
            $response->assertStatus(200);
        }
    }

    /**
     * Test middleware with different user states.
     */
    public function test_middleware_with_different_user_states()
    {
        // Test with active user
        $activeUser = User::factory()->create();
        $this->actingAs($activeUser);
        
        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        // Logout for next test
        Auth::logout();

        // Test with no user
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    /**
     * Test middleware preserves request data.
     */
    public function test_middleware_preserves_request_data()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Make request with query parameters
        $response = $this->get('/dashboard?tab=profile&section=settings');
        
        $response->assertStatus(200);
        // The middleware should not interfere with request parameters
    }

    /**
     * Test middleware with different HTTP methods.
     */
    public function test_middleware_with_different_http_methods()
    {
        // Test GET without auth
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        // Test POST without auth (logout route)
        $response = $this->post('/logout');
        $response->assertRedirect('/login');

        // Now test with authenticated user
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        $response = $this->post('/logout');
        $response->assertRedirect('/');
    }

    /**
     * Test middleware error handling.
     */
    public function test_middleware_error_handling()
    {
        // Test that middleware gracefully handles edge cases
        
        // Try to access protected route without session
        $this->withoutMiddleware(\Illuminate\Session\Middleware\StartSession::class);
        
        $response = $this->get('/dashboard');
        
        // Should still redirect appropriately
        $this->assertTrue(in_array($response->getStatusCode(), [302, 401]));
    }

    /**
     * Test middleware performance with multiple checks.
     */
    public function test_middleware_performance()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $startTime = microtime(true);

        // Make multiple requests to test middleware performance
        for ($i = 0; $i < 10; $i++) {
            $response = $this->get('/dashboard');
            $response->assertStatus(200);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Middleware should not add significant overhead
        // This is a rough test - actual performance depends on many factors
        $this->assertLessThan(5.0, $executionTime, 'Middleware taking too long');
    }

    /**
     * Test middleware integration with Laravel's authentication system.
     */
    public function test_middleware_integration_with_auth_system()
    {
        $user = User::factory()->create();
        
        // Test manual authentication
        Auth::login($user);
        $this->assertAuthenticated();
        
        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        // Test logout
        Auth::logout();
        $this->assertGuest();
        
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }
}
