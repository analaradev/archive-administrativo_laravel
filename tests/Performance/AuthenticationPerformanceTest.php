<?php

namespace Tests\Performance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\User;

/**
 * Tests de Performance para autenticación y operaciones básicas
 * 
 * Estos tests verifican que las operaciones críticas del sistema
 * se ejecuten dentro de tiempos aceptables bajo diferentes cargas.
 */
class AuthenticationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private const MAX_LOGIN_TIME = 200; // milisegundos
    private const MAX_DASHBOARD_TIME = 150; // milisegundos
    private const MAX_DB_QUERY_TIME = 50; // milisegundos
    private const CONCURRENT_USERS = 10;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Limpiar cache antes de cada test
        Cache::flush();
        
        // Configurar logging para performance
        Log::debug('=== INICIANDO TEST DE PERFORMANCE ===');
    }

    protected function tearDown(): void
    {
        Log::debug('=== FINALIZANDO TEST DE PERFORMANCE ===');
        parent::tearDown();
    }

    /**
     * Test de performance de login básico
     */
    public function test_login_performance_under_normal_load(): void
    {
        // Crear usuario de prueba
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $startTime = microtime(true);

        // Ejecutar login
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // convertir a ms

        // Verificar que el login sea exitoso
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        // Verificar tiempo de respuesta
        $this->assertLessThan(
            self::MAX_LOGIN_TIME,
            $executionTime,
            "Login tardó {$executionTime}ms, máximo permitido: " . self::MAX_LOGIN_TIME . "ms"
        );

        Log::info("Performance Login: {$executionTime}ms");
    }

    /**
     * Test de performance del dashboard con datos
     */
    public function test_dashboard_performance_with_data(): void
    {
        // Crear usuario autenticado
        $user = User::factory()->create();
        $this->actingAs($user);

        // Crear datos adicionales si es necesario
        // (en el futuro, cuando tengamos más modelos)

        $startTime = microtime(true);

        // Cargar dashboard
        $response = $this->get('/dashboard');

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        // Verificar respuesta exitosa
        $response->assertOk();
        $response->assertViewIs('dashboard');

        // Verificar tiempo de respuesta
        $this->assertLessThan(
            self::MAX_DASHBOARD_TIME,
            $executionTime,
            "Dashboard tardó {$executionTime}ms, máximo permitido: " . self::MAX_DASHBOARD_TIME . "ms"
        );

        Log::info("Performance Dashboard: {$executionTime}ms");
    }

    /**
     * Test de performance de consultas a base de datos
     */
    public function test_database_query_performance(): void
    {
        // Crear múltiples usuarios para simular datos reales
        User::factory()->count(100)->create();

        // Test 1: Consulta simple
        $startTime = microtime(true);
        $users = User::where('email_verified_at', '!=', null)->count();
        $endTime = microtime(true);
        $queryTime1 = ($endTime - $startTime) * 1000;

        $this->assertLessThan(
            self::MAX_DB_QUERY_TIME,
            $queryTime1,
            "Consulta simple tardó {$queryTime1}ms, máximo permitido: " . self::MAX_DB_QUERY_TIME . "ms"
        );

        // Test 2: Consulta con paginación
        $startTime = microtime(true);
        $paginatedUsers = User::paginate(10);
        $endTime = microtime(true);
        $queryTime2 = ($endTime - $startTime) * 1000;

        $this->assertLessThan(
            self::MAX_DB_QUERY_TIME,
            $queryTime2,
            "Consulta paginada tardó {$queryTime2}ms, máximo permitido: " . self::MAX_DB_QUERY_TIME . "ms"
        );

        // Test 3: Consulta con ordering
        $startTime = microtime(true);
        $orderedUsers = User::orderBy('created_at', 'desc')->limit(20)->get();
        $endTime = microtime(true);
        $queryTime3 = ($endTime - $startTime) * 1000;

        $this->assertLessThan(
            self::MAX_DB_QUERY_TIME,
            $queryTime3,
            "Consulta ordenada tardó {$queryTime3}ms, máximo permitido: " . self::MAX_DB_QUERY_TIME . "ms"
        );

        Log::info("Performance DB - Simple: {$queryTime1}ms, Paginada: {$queryTime2}ms, Ordenada: {$queryTime3}ms");
    }

    /**
     * Test de performance con cache
     */
    public function test_cache_performance(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Primer acceso (sin cache)
        $startTime = microtime(true);
        $response1 = $this->get('/dashboard');
        $endTime = microtime(true);
        $timeWithoutCache = ($endTime - $startTime) * 1000;

        $response1->assertOk();

        // Segundo acceso (con cache, si está implementado)
        $startTime = microtime(true);
        $response2 = $this->get('/dashboard');
        $endTime = microtime(true);
        $timeWithCache = ($endTime - $startTime) * 1000;

        $response2->assertOk();

        // El segundo acceso debería ser igual o más rápido
        $this->assertLessThanOrEqual(
            $timeWithoutCache,
            $timeWithCache,
            "El segundo acceso con cache tardó más que el primero: {$timeWithCache}ms vs {$timeWithoutCache}ms"
        );

        Log::info("Performance Cache - Sin cache: {$timeWithoutCache}ms, Con cache: {$timeWithCache}ms");
    }

    /**
     * Test de carga simultánea (simulado)
     */
    public function test_concurrent_login_simulation(): void
    {
        // Crear múltiples usuarios
        $users = User::factory()->count(self::CONCURRENT_USERS)->create();
        
        $totalTime = 0;
        $maxTime = 0;
        $minTime = PHP_INT_MAX;
        $failures = 0;

        foreach ($users as $index => $user) {
            $startTime = microtime(true);

            try {
                $response = $this->post('/login', [
                    'email' => $user->email,
                    'password' => 'password' // La factory usa 'password' por defecto
                ]);

                $endTime = microtime(true);
                $executionTime = ($endTime - $startTime) * 1000;

                if ($response->status() === 302) { // Redirect exitoso
                    $totalTime += $executionTime;
                    $maxTime = max($maxTime, $executionTime);
                    $minTime = min($minTime, $executionTime);
                } else {
                    $failures++;
                }

                // Logout para el siguiente test
                $this->post('/logout');

            } catch (\Exception $e) {
                $failures++;
                Log::error("Error en login concurrente {$index}: " . $e->getMessage());
            }
        }

        $successfulLogins = self::CONCURRENT_USERS - $failures;
        $averageTime = $successfulLogins > 0 ? $totalTime / $successfulLogins : 0;

        // Verificaciones
        $this->assertLessThan(
            self::CONCURRENT_USERS * 0.1, // Máximo 10% de fallos
            $failures,
            "Demasiados fallos en login concurrente: {$failures}/" . self::CONCURRENT_USERS
        );

        $this->assertLessThan(
            self::MAX_LOGIN_TIME * 2, // El tiempo máximo bajo carga puede ser el doble
            $maxTime,
            "Tiempo máximo de login bajo carga excesivo: {$maxTime}ms"
        );

        Log::info("Performance Concurrente - Promedio: {$averageTime}ms, Máximo: {$maxTime}ms, Mínimo: {$minTime}ms, Fallos: {$failures}");
    }

    /**
     * Test de performance de memoria
     */
    public function test_memory_usage(): void
    {
        $initialMemory = memory_get_usage(true);

        // Crear usuario y hacer login
        $user = User::factory()->create();
        $this->actingAs($user);

        // Realizar operaciones típicas
        $this->get('/dashboard');
        $this->get('/dashboard'); // Segunda vez para verificar memory leaks

        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;
        $memoryIncreaseKB = $memoryIncrease / 1024;

        // Verificar que el aumento de memoria sea razonable (menos de 5MB)
        $this->assertLessThan(
            5 * 1024 * 1024, // 5MB
            $memoryIncrease,
            "Aumento de memoria excesivo: {$memoryIncreaseKB}KB"
        );

        Log::info("Performance Memoria - Inicial: " . ($initialMemory/1024) . "KB, Final: " . ($finalMemory/1024) . "KB, Aumento: {$memoryIncreaseKB}KB");
    }

    /**
     * Test de performance de sesiones
     */
    public function test_session_performance(): void
    {
        $user = User::factory()->create();

        // Test de múltiples operaciones de sesión
        $startTime = microtime(true);

        for ($i = 0; $i < 10; $i++) {
            // Login
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'password'
            ]);

            // Verificar sesión
            $this->assertAuthenticatedAs($user);

            // Logout
            $this->post('/logout');
            $this->assertGuest();
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $averageTime = $totalTime / 10;

        // Verificar que cada operación de login/logout sea rápida
        $this->assertLessThan(
            self::MAX_LOGIN_TIME,
            $averageTime,
            "Promedio de login/logout tardó {$averageTime}ms, máximo permitido: " . self::MAX_LOGIN_TIME . "ms"
        );

        Log::info("Performance Sesiones - Total: {$totalTime}ms, Promedio: {$averageTime}ms");
    }

    /**
     * Test de performance de validación
     */
    public function test_validation_performance(): void
    {
        // Test de validación de datos válidos
        $startTime = microtime(true);

        for ($i = 0; $i < 20; $i++) {
            $this->post('/register', [
                'name' => "Test User {$i}",
                'email' => "test{$i}@example.com",
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $averageTime = $totalTime / 20;

        // Verificar que la validación sea rápida
        $this->assertLessThan(
            100, // 100ms por validación
            $averageTime,
            "Validación promedio tardó {$averageTime}ms, máximo permitido: 100ms"
        );

        // Test de validación de datos inválidos
        $startTime = microtime(true);

        for ($i = 0; $i < 10; $i++) {
            $this->post('/register', [
                'name' => '', // Inválido
                'email' => 'invalid-email', // Inválido
                'password' => '123', // Muy corto
                'password_confirmation' => 'different', // No coincide
            ]);
        }

        $endTime = microtime(true);
        $validationTime = ($endTime - $startTime) * 1000;
        $averageValidationTime = $validationTime / 10;

        $this->assertLessThan(
            150, // 150ms por validación de errores
            $averageValidationTime,
            "Validación de errores promedio tardó {$averageValidationTime}ms, máximo permitido: 150ms"
        );

        Log::info("Performance Validación - Válida: {$averageTime}ms, Errores: {$averageValidationTime}ms");
    }

    /**
     * Helper: Generar reporte de performance
     */
    protected function generatePerformanceReport(): void
    {
        $report = [
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'database' => config('database.default'),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
        ];

        Log::info('Performance Report', $report);
    }
}
