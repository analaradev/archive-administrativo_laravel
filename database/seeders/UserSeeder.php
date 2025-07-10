<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear un usuario administrador por defecto
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin123'),
            ]
        );

        // Crear algunos usuarios de prueba
        User::factory()->create([
            'name' => 'Usuario Test',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        // Crear usuarios adicionales si estamos en desarrollo
        if (app()->environment(['local', 'development'])) {
            User::factory(10)->create();
        }
    }
}
