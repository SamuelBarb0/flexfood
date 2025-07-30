<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Limpia cache de roles/permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // === Permisos globales ===
        $permissions = [
            // Administración
            'ver usuarios',
            'crear usuarios',
            'editar usuarios',
            'eliminar usuarios',
            'ver reportes',
            'configurar sistema',

            // Comandas
            'ver comandas',
            'crear comandas',
            'editar comandas',
            'actualizar estado comanda',

            // Menú
            'ver menu',
            'gestionar menu',

            // Mesas
            'ver mesas',
            'asignar mesas',
            'cerrar cuenta',

            // Historial y estadísticas
            'ver historial',
            'ver analiticas',
            'ver dashboard',
        ];

        foreach ($permissions as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        // === Rol Administrador ===
        $admin = Role::firstOrCreate(['name' => 'administrador']);
        $admin->givePermissionTo(Permission::all());

        // === Rol Cocina ===
        $chef = Role::firstOrCreate(['name' => 'cocina']);
        $chef->givePermissionTo([
            'ver comandas',
            'actualizar estado comanda',
            'ver menu',
        ]);

        // === Rol Mesero ===
        $mesero = Role::firstOrCreate(['name' => 'mesero']);
        $mesero->givePermissionTo([
            'ver dashboard',
            'ver historial',
            'ver analiticas',
            'ver comandas',
            'crear comandas',
            'asignar mesas',
            'ver mesas',
            'cerrar cuenta',
        ]);
    }
}
