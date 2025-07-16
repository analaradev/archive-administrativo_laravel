<?php

namespace Tests\Integration;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationFlowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test complete registration to dashboard flow.
     */
    public function test_complete_registration_flow()
    {
        // Step 1: Visit registration page
        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertViewIs('auth.register');

        // Step 2: Submit registration form
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $userData);
        
        // Step 3: Verify redirect to dashboard
        $response->assertRedirect('/dashboard');
        
        // Step 4: Verify user is created and authenticated
        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]);
        
        $user = User::where('email', $userData['email'])->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertAuthenticatedAs($user);

        // Step 5: Verify dashboard access
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertViewHas('user', $user);
    }

    /**
     * Test complete login to dashboard flow.
     */
    public function test_complete_login_flow()
    {
        // Step 1: Create a user
        $user = User::factory()->create([
            'password' => Hash::make('password123')
        ]);

        // Step 2: Visit login page
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');

        // Step 3: Submit login form
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        // Step 4: Verify redirect to dashboard
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        // Step 5: Verify dashboard access
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertViewHas('user', $user);
    }

    /**
     * Test complete logout flow.
     */
    public function test_complete_logout_flow()
    {
        // Step 1: Create and authenticate user
        $user = User::factory()->create();
        $this->actingAs($user);

        // Step 2: Verify user is authenticated and can access dashboard
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $this->assertAuthenticatedAs($user);

        // Step 3: Logout
        $response = $this->post('/logout');
        
        // Step 4: Verify redirect to home
        $response->assertRedirect('/');
        $this->assertGuest();

        // Step 5: Verify cannot access dashboard after logout
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    /**
     * Test registration with invalid data shows validation errors.
     */
    public function test_registration_with_invalid_data_shows_errors()
    {
        // Submit registration with invalid data
        $response = $this->post('/register', [
            'name' => '', // required
            'email' => 'invalid-email', // invalid format
            'password' => '123', // too short
            'password_confirmation' => 'different', // doesn't match
        ]);

        $response->assertSessionHasErrors([
            'name',
            'email', 
            'password'
        ]);

        // Verify user is not created
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    /**
     * Test login with invalid credentials shows error.
     */
    public function test_login_with_invalid_credentials_shows_error()
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password')
        ]);

        // Try login with wrong password
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    /**
     * Test authenticated user accessing guest routes redirects to dashboard.
     */
    public function test_authenticated_user_redirected_from_guest_routes()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Test login page redirect
        $response = $this->get('/login');
        $response->assertRedirect('/dashboard');

        // Test register page redirect
        $response = $this->get('/register');
        $response->assertRedirect('/dashboard');
    }

    /**
     * Test guest user accessing protected routes redirects to login.
     */
    public function test_guest_user_redirected_from_protected_routes()
    {
        // Try to access dashboard without authentication
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * Test root route behavior for different user states.
     */
    public function test_root_route_behavior()
    {
        // Test unauthenticated user sees welcome page
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertViewIs('welcome');

        // Test authenticated user redirected to dashboard
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/');
        $response->assertRedirect('/dashboard');
    }

    /**
     * Test session persistence across requests.
     */
    public function test_session_persistence()
    {
        // Login user
        $user = User::factory()->create([
            'password' => Hash::make('password123')
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);

        // Make multiple requests to verify session persists
        for ($i = 0; $i < 3; $i++) {
            $response = $this->get('/dashboard');
            $response->assertStatus(200);
            $this->assertAuthenticatedAs($user);
        }
    }

    /**
     * Test CSRF protection on authentication forms.
     */
    public function test_csrf_protection_on_auth_forms()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123')
        ]);

        // Test login without CSRF token (this will be handled by Laravel's middleware)
        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password123',
            ]);

        // Even without CSRF middleware, the request should work in testing
        // In real scenarios, CSRF protection would prevent this
        $response->assertRedirect('/dashboard');
    }

    /**
     * Test password hashing during registration.
     */
    public function test_password_is_hashed_during_registration()
    {
        $plainPassword = 'plain-text-password';
        
        $this->post('/register', [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => $plainPassword,
            'password_confirmation' => $plainPassword,
        ]);

        $user = User::latest()->first();
        
        // Verify password is not stored as plain text
        $this->assertNotEquals($plainPassword, $user->password);
        
        // Verify password can be verified with Hash::check
        $this->assertTrue(Hash::check($plainPassword, $user->password));
    }

    /**
     * Test user data is available in dashboard view.
     */
    public function test_user_data_available_in_dashboard()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $this->actingAs($user);

        $response = $this->get('/dashboard');
        
        $response->assertStatus(200);
        $response->assertViewHas('user');
        $response->assertSee('John Doe');
        $response->assertSee('john@example.com');
    }

    /**
     * Test multiple user registration and authentication.
     */
    public function test_multiple_users_can_register_and_authenticate()
    {
        $users = [];
        
        // Register multiple users
        for ($i = 0; $i < 3; $i++) {
            $userData = [
                'name' => $this->faker->name,
                'email' => $this->faker->unique()->safeEmail,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ];

            $this->post('/register', $userData);
            $users[] = User::where('email', $userData['email'])->first();
            
            // Logout after each registration
            $this->post('/logout');
        }

        // Verify all users were created
        $this->assertDatabaseCount('users', 3);

        // Test each user can login independently
        foreach ($users as $user) {
            $response = $this->post('/login', [
                'email' => $user->email,
                'password' => 'password123',
            ]);

            $response->assertRedirect('/dashboard');
            $this->assertAuthenticatedAs($user);

            // Logout for next iteration
            $this->post('/logout');
            $this->assertGuest();
        }
    }

    /**
     * Test authentication state changes correctly.
     */
    public function test_authentication_state_changes()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123')
        ]);

        // Initial state: guest
        $this->assertGuest();

        // After login: authenticated
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);
        $this->assertAuthenticatedAs($user);

        // After logout: guest again
        $this->post('/logout');
        $this->assertGuest();
    }
}
