<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ValidationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test comprehensive email validation rules.
     */
    public function test_email_validation_comprehensive()
    {
        // Test various invalid email formats
        $invalidEmails = [
            '',                          // Empty
            'invalid-email',             // No @ symbol
            '@example.com',             // Missing local part
            'test@',                    // Missing domain
            'test..test@example.com',   // Double dots
            'test@example.',            // Missing TLD
            'test@.example.com',        // Leading dot in domain
            'test@example..com',        // Double dots in domain
            'test space@example.com',   // Space in local part
            'test@exam ple.com',        // Space in domain
            'very-long-email-address-that-exceeds-the-maximum-length-allowed-for-email-addresses-in-most-systems@example.com', // Too long
        ];

        foreach ($invalidEmails as $email) {
            $response = $this->post('/login', [
                'email' => $email,
                'password' => 'password123',
            ]);

            $response->assertSessionHasErrors(['email']);
        }

        // Test valid email formats
        $validEmails = [
            'test@example.com',
            'user.name@example.com',
            'user+tag@example.com',
            'user123@example.co.uk',
            'a@b.co',
        ];

        $user = User::factory()->create([
            'email' => 'valid@example.com',
            'password' => Hash::make('password123')
        ]);

        foreach ($validEmails as $email) {
            $response = $this->post('/login', [
                'email' => $email,
                'password' => 'password123',
            ]);

            // Should not have email format errors (may have auth errors)
            $this->assertFalse(
                session()->hasOldInput('email') && 
                session('errors') && 
                session('errors')->has('email') &&
                str_contains(session('errors')->first('email'), 'formato')
            );
        }
    }

    /**
     * Test password validation rules extensively.
     */
    public function test_password_validation_comprehensive()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ];

        // Test minimum length requirement
        $shortPasswords = ['', '1', '12', '123', '1234', '12345', '123456', '1234567'];
        
        foreach ($shortPasswords as $password) {
            $response = $this->post('/register', array_merge($userData, [
                'password' => $password,
                'password_confirmation' => $password,
            ]));

            $response->assertSessionHasErrors(['password']);
        }

        // Test password confirmation mismatch
        $response = $this->post('/register', array_merge($userData, [
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]));

        $response->assertSessionHasErrors(['password']);

        // Test valid passwords
        $validPasswords = [
            'password123',
            '12345678',
            'mySecretPass',
            'P@ssw0rd!',
            'very-long-password-that-should-be-valid',
        ];

        foreach ($validPasswords as $password) {
            $response = $this->post('/register', [
                'name' => 'Test User',
                'email' => $this->faker->unique()->safeEmail,
                'password' => $password,
                'password_confirmation' => $password,
            ]);

            $response->assertRedirect('/dashboard');
        }
    }

    /**
     * Test name validation rules.
     */
    public function test_name_validation_rules()
    {
        $validData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // Test empty name
        $response = $this->post('/register', array_merge($validData, [
            'name' => '',
        ]));
        $response->assertSessionHasErrors(['name']);

        // Test very long name (over 255 characters)
        $longName = str_repeat('a', 256);
        $response = $this->post('/register', array_merge($validData, [
            'name' => $longName,
        ]));
        $response->assertSessionHasErrors(['name']);

        // Test valid names
        $validNames = [
            'John',
            'John Doe',
            'María García',
            'Jean-Pierre',
            "O'Connor",
            'José María de la Cruz',
            str_repeat('a', 255), // Exactly 255 characters
        ];

        foreach ($validNames as $name) {
            $response = $this->post('/register', [
                'name' => $name,
                'email' => $this->faker->unique()->safeEmail,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            $response->assertRedirect('/dashboard');
        }
    }

    /**
     * Test email uniqueness validation.
     */
    public function test_email_uniqueness_validation()
    {
        // Create existing user
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com'
        ]);

        // Try to register with same email
        $response = $this->post('/register', [
            'name' => 'New User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);

        // Test case insensitive uniqueness
        $response = $this->post('/register', [
            'name' => 'New User',
            'email' => 'EXISTING@EXAMPLE.COM',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);

        // Verify only one user exists with this email
        $this->assertEquals(1, User::where('email', 'existing@example.com')->count());
    }

    /**
     * Test login validation with missing fields.
     */
    public function test_login_validation_missing_fields()
    {
        // Test missing email
        $response = $this->post('/login', [
            'password' => 'password123',
        ]);
        $response->assertSessionHasErrors(['email']);

        // Test missing password
        $response = $this->post('/login', [
            'email' => 'test@example.com',
        ]);
        $response->assertSessionHasErrors(['password']);

        // Test both missing
        $response = $this->post('/login', []);
        $response->assertSessionHasErrors(['email', 'password']);
    }

    /**
     * Test validation with special characters and edge cases.
     */
    public function test_validation_special_characters()
    {
        // Test name with special characters
        $specialNames = [
            'José María',
            'François',
            'Müller',
            'O\'Brien',
            'Jean-Pierre',
            '李小明',
            'محمد',
        ];

        foreach ($specialNames as $name) {
            $response = $this->post('/register', [
                'name' => $name,
                'email' => $this->faker->unique()->safeEmail,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            $response->assertRedirect('/dashboard');
        }

        // Test emails with special but valid characters
        $specialEmails = [
            'user+tag@example.com',
            'user.name@example.com',
            'user_name@example.com',
            'user-name@example.com',
            'user123@example.com',
        ];

        foreach ($specialEmails as $email) {
            $response = $this->post('/register', [
                'name' => 'Test User',
                'email' => $email,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            $response->assertRedirect('/dashboard');
        }
    }

    /**
     * Test validation with whitespace handling.
     */
    public function test_validation_whitespace_handling()
    {
        // Test trimming of whitespace
        $response = $this->post('/register', [
            'name' => '  John Doe  ',
            'email' => '  test@example.com  ',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');

        // Verify data was trimmed
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('test@example.com', $user->email);
    }

    /**
     * Test validation error messages are user-friendly.
     */
    public function test_validation_error_messages()
    {
        // Test that error messages are in Spanish and user-friendly
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password']);

        $errors = session('errors');
        
        // Verify error messages exist and are not just field names
        $this->assertNotEmpty($errors->first('name'));
        $this->assertNotEmpty($errors->first('email'));
        $this->assertNotEmpty($errors->first('password'));
    }

    /**
     * Test validation with array injection attempts.
     */
    public function test_validation_array_injection_protection()
    {
        // Attempt to send arrays instead of strings
        $response = $this->post('/register', [
            'name' => ['malicious', 'array'],
            'email' => ['test@example.com'],
            'password' => ['password123'],
            'password_confirmation' => ['password123'],
        ]);

        // Should handle gracefully and show validation errors
        $response->assertSessionHasErrors();
    }

    /**
     * Test validation with extremely long inputs.
     */
    public function test_validation_with_extremely_long_inputs()
    {
        $veryLongString = str_repeat('a', 10000);

        $response = $this->post('/register', [
            'name' => $veryLongString,
            'email' => $veryLongString . '@example.com',
            'password' => $veryLongString,
            'password_confirmation' => $veryLongString,
        ]);

        $response->assertSessionHasErrors();
    }

    /**
     * Test validation with null values.
     */
    public function test_validation_with_null_values()
    {
        $response = $this->post('/register', [
            'name' => null,
            'email' => null,
            'password' => null,
            'password_confirmation' => null,
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
    }

    /**
     * Test old input preservation on validation errors.
     */
    public function test_old_input_preservation()
    {
        $inputData = [
            'name' => 'John Doe',
            'email' => 'invalid-email', // This will cause validation error
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $inputData);

        $response->assertSessionHasErrors(['email']);
        
        // Verify old input is preserved (except password fields)
        $this->assertEquals('John Doe', old('name'));
        $this->assertEquals('invalid-email', old('email'));
        
        // Password fields should not be preserved for security
        $this->assertEmpty(old('password'));
        $this->assertEmpty(old('password_confirmation'));
    }

    /**
     * Test validation with mixed case email normalization.
     */
    public function test_email_case_normalization()
    {
        // Register user with mixed case email
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'Test.User@EXAMPLE.COM',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');

        // Verify email was stored in lowercase
        $user = User::where('email', 'test.user@example.com')->first();
        $this->assertNotNull($user);

        // Test login with different case variations
        $this->post('/logout');

        $loginVariations = [
            'test.user@example.com',
            'Test.User@example.com',
            'TEST.USER@EXAMPLE.COM',
            'test.user@EXAMPLE.COM',
        ];

        foreach ($loginVariations as $email) {
            $response = $this->post('/login', [
                'email' => $email,
                'password' => 'password123',
            ]);

            $response->assertRedirect('/dashboard');
            $this->post('/logout');
        }
    }

    /**
     * Test validation performance with large datasets.
     */
    public function test_validation_performance()
    {
        // Create many users to test uniqueness validation performance
        User::factory(100)->create();

        $startTime = microtime(true);

        // Test registration with new email
        $response = $this->post('/register', [
            'name' => 'Performance Test',
            'email' => 'performance@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertRedirect('/dashboard');
        
        // Validation should complete within reasonable time
        $this->assertLessThan(2.0, $executionTime, 'Validation taking too long');
    }

    /**
     * Test validation with concurrent requests.
     */
    public function test_validation_concurrency()
    {
        // This test simulates multiple users trying to register with the same email
        $email = 'concurrent@test.com';
        
        // First registration should succeed
        $response1 = $this->post('/register', [
            'name' => 'User One',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response1->assertRedirect('/dashboard');

        // Second registration with same email should fail
        $response2 = $this->post('/register', [
            'name' => 'User Two',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response2->assertSessionHasErrors(['email']);

        // Verify only one user exists
        $this->assertEquals(1, User::where('email', $email)->count());
    }
}
