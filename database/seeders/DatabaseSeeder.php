<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Ejecuta el seeder de roles y permisos
        $this->call(RolesAndPermissionsSeeder::class);

        // Crea un usuario administrador
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@flexfood.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password123'), // puedes cambiar esta contraseña
            ]
        );

        // Asigna el rol administrador
        $adminUser->assignRole('administrador');
    }
}
