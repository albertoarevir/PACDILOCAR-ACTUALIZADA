<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $permisosConfiguracion = [
            'GRADOS DEL PERSONAL',
            'DEPARTAMENTOS',
            'ESPECIES O SERVICIOS',
            'CLASIFICADOR',
            'DESCRIPCION CLASIFICADOR',
            'UNIDAD DE COMPRAS',
            'ESTADO DEL REGISTRO',
            'MODALIDAD DE COMPRA',
            'ESTADO DE LICITACION',
            'ESTADO DE COMPRAS',
            'FUENTE DE FINANCIAMIENTO',
        ];

        foreach ($permisosConfiguracion as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $admin = Role::where('name', 'ADMINISTRADOR')->first();
        if ($admin) {
            foreach ($permisosConfiguracion as $name) {
                if (!$admin->hasPermissionTo($name)) {
                    $admin->givePermissionTo($name);
                }
            }
        }

        // Limpiar caché de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // No revertir: para evitar eliminar permisos asignados a roles
    }
};
