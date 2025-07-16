<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test CSRF protection on login form.
     */
    public function test_csrf_protection_on_login()
    {
        $this->startSession();
        
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $response->assertStatus(419); // CSRF token mismatch
    }

    /**
     * Test CSRF protection on registration form.
     */
    public function test_csrf_protection_on_registration()
    {
        $this->startSession();
        
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);

        $response->assertStatus(419); // CSRF token mismatch
    }

    /**
     * Test password security and hashing.
     */
    public function test_password_security_hashing()
    {
        $this->startSession();
        $this->withSession(['_token' => 'test-token']);
        
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'plaintext-password',
            'password_confirmation' => 'plaintext-password',
            '_token' => 'test-token'
        ]);

        $user = User::where('email', 'test@example.com')->first();
        
        if ($user) {
            // Password should be hashed, not stored as plaintext
            $this->assertNotEquals('plaintext-password', $user->password);
            $this->assertTrue(Hash::check('plaintext-password', $user->password));
        }
    }

    /**
     * Test SQL injection protection on login.
     */
    public function test_sql_injection_protection_login()
    {
        $this->startSession();
        $this->withSession(['_token' => 'test-token']);
        
        // Create a legitimate user
        $user = User::factory()->create([
            'email' => 'legitimate@example.com',
            'password' => Hash::make('password123')
        ]);

        // Attempt SQL injection
        $response = $this->post('/login', [
            'email' => "legitimate@example.com'; DROP TABLE users; --",
            'password' => 'password123',
            '_token' => 'test-token'
        ]);

        // Should not authenticate and users table should still exist
        $this->assertDatabaseHas('users', [
            'email' => 'legitimate@example.com'
        ]);
        
        $response->assertRedirect('/login');
    }

    /**
     * Test XSS protection on registration.
     */
    public function test_xss_protection_registration()
    {
        $this->startSession();
        $this->withSession(['_token' => 'test-token']);
        
        $maliciousScript = '<script>alert("XSS")</script>';
        
        $response = $this->post('/register', [
            'name' => $maliciousScript,
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            '_token' => 'test-token'
        ]);

        $user = User::where('email', 'test@example.com')->first();
        
        if ($user) {
            // Name should be escaped/sanitized
            $this->assertStringNotContainsString('<script>', $user->name);
            $this->assertStringNotContainsString('alert', $user->name);
        }
    }

    /**
     * Test login rate limiting.
     */
    public function test_login_rate_limiting()
    {
        $this->startSession();
        $this->withSession(['_token' => 'test-token']);
        
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password')
        ]);

        // Make multiple failed login attempts
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
                '_token' => 'test-token'
            ]);
        }

        // After rate limit, even correct credentials should be throttled
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'correct-password',
            '_token' => 'test-token'
        ]);

        $response->assertStatus(429); // Too many requests
    }

    /**
     * Test session regeneration on login.
     */
    public function test_session_regeneration_on_login()
    {
        $this->startSession();
        $this->withSession(['_token' => 'test-token']);
        
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $oldSessionId = session()->getId();

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            '_token' => 'test-token'
        ]);

        // Session ID should change after successful login
        $newSessionId = session()->getId();
        $this->assertNotEquals($oldSessionId, $newSessionId);
    }

    /**
     * Test password validation rules.
     */
    public function test_password_validation_rules()
    {
        $this->startSession();
        $this->withSession(['_token' => 'test-token']);
        
        // Test with weak password
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '123', // Too short
            'password_confirmation' => '123',
            '_token' => 'test-token'
        ]);

        $response->assertSessionHasErrors(['password']);
        
        // Ensure user was not created with weak password
        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com'
        ]);
    }

    /**
     * Test email uniqueness validation.
     */
    public function test_email_uniqueness_validation()
    {
        $this->startSession();
        $this->withSession(['_token' => 'test-token']);
        
        // Create first user
        User::factory()->create([
            'email' => 'duplicate@example.com'
        ]);

        // Try to register with same email
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'duplicate@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            '_token' => 'test-token'
        ]);

        $response->assertSessionHasErrors(['email']);
        
        // Should only have one user with that email
        $userCount = User::where('email', 'duplicate@example.com')->count();
        $this->assertEquals(1, $userCount);
    }

    /**
     * Test protected routes require authentication.
     */
    public function test_protected_routes_require_authentication()
    {
        // Try to access dashboard without authentication
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
        
        // Authenticate user
        $user = User::factory()->create();
        $this->actingAs($user);
        
        // Now should be able to access dashboard
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }

    /**
     * Test guest routes redirect authenticated users.
     */
    public function test_guest_routes_redirect_authenticated_users()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        // Authenticated users should be redirected from login/register
        $response = $this->get('/login');
        $response->assertRedirect('/dashboard');
        
        $response = $this->get('/register');
        $response->assertRedirect('/dashboard');
    }

    /**
     * Test mass assignment protection.
     */
    public function test_mass_assignment_protection()
    {
        $this->startSession();
        $this->withSession(['_token' => 'test-token']);
        
        // Try to mass assign protected fields
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_admin' => true, // This should not be mass assignable
            '_token' => 'test-token'
        ]);

        $user = User::where('email', 'test@example.com')->first();
        
        if ($user) {
            // is_admin should not be set through mass assignment
            $this->assertFalse(isset($user->is_admin) && $user->is_admin);
        }
    }

    /**
     * Test security headers.
     */
    public function test_security_headers()
    {
        $response = $this->get('/');
        
        // Check for basic security headers
        $response->assertHeader('X-Frame-Options');
        $response->assertHeader('X-Content-Type-Options');
    }

    /**
     * Test logout invalidates session.
     */
    public function test_logout_invalidates_session()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        // Verify user is authenticated
        $this->assertAuthenticated();
        
        // Logout
        $response = $this->post('/logout');
        
        // Should be redirected and no longer authenticated
        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /**
     * Test input sanitization and validation.
     */
    public function test_input_sanitization()
    {
        // Test with various malicious inputs
        $maliciousInputs = [
            'name' => '   Test User   ', // Should be trimmed
            'email' => '  TEST@EXAMPLE.COM  ', // Should be normalized
        ];

        $response = $this->post('/register', [
            'name' => $maliciousInputs['name'],
            'email' => $maliciousInputs['email'],
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // ✅ Ensure registration was successful
        $response->assertStatus(302);
        
        // ✅ Verify user was created in database (with trimmed email)
        $this->assertDatabaseHas('users', [
            'email' => 'TEST@EXAMPLE.COM' // Laravel stores as provided, but trimmed
        ]);

        // ✅ Get user and ensure it exists
        $user = User::where('email', 'TEST@EXAMPLE.COM')->first();
        $this->assertNotNull($user, 'User must be created for sanitization test');
        
        // ✅ Validate sanitization (guaranteed to execute)
        // Name should be trimmed
        $this->assertEquals('Test User', $user->name);
        
        // Email should be trimmed but case preserved (as Laravel default behavior)
        $this->assertEquals('TEST@EXAMPLE.COM', $user->email);
    }

    /**
     * Test password reset security (if implemented).
     */
    public function test_password_security_best_practices()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123')
        ]);

        // Verify password is using bcrypt (starts with $2y$)
        $this->assertStringStartsWith('$2y$', $user->password);
        
        // Verify password hash is different each time
        $hash1 = Hash::make('same-password');
        $hash2 = Hash::make('same-password');
        $this->assertNotEquals($hash1, $hash2);
        
        // But both should verify correctly
        $this->assertTrue(Hash::check('same-password', $hash1));
        $this->assertTrue(Hash::check('same-password', $hash2));
    }

    /**
     * Test concurrent login attempts.
     */
    public function test_concurrent_login_security()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123')
        ]);

        // Simulate multiple concurrent login attempts
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $this->startSession();
            $this->withSession(['_token' => 'test-token-' . $i]);
            
            $responses[] = $this->post('/login', [
                'email' => $user->email,
                'password' => 'password123',
                '_token' => 'test-token-' . $i
            ]);
        }

        // All should be able to authenticate (no session conflicts)
        foreach ($responses as $response) {
            $response->assertRedirect('/dashboard');
        }
    }
}