<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
      
        $admin = Role::create(['name' => 'ADMINISTRADOR', 'guard_name' => 'web']);
        $director = Role::create(['name' => 'DIRECTOR', 'guard_name' => 'web']);
        $asesor_tecnico = Role::create(['name' => 'ASESOR_TECNICO', 'guard_name' => 'web']);
        $alta_reparticion = Role::create(['name' => 'ALTA_REPARTICION', 'guard_name' => 'web']);
        $jefe_departamento = Role::create(['name' => 'JEFE_REPARTICION', 'guard_name' => 'web']);
        $jefe_revisor = Role::create(['name' => 'JEFE_REVISOR', 'guard_name' => 'web']);
        $digitador = Role::create(['name' => 'DIGITADOR', 'guard_name' => 'web']);
        $usuario_basico = Role::create(['name' => 'USUARIO_BASICO', 'guard_name' => 'web']);

        // Crear permisos para proyectos
        Permission::create(['name' => 'INGRESAR LICITACION', 'guard_name' => 'web']);
        Permission::create(['name' => 'MODIFICAR PROYECTO', 'guard_name' => 'web']);
        Permission::create(['name' => 'ELIMINAR PROYECTO', 'guard_name' => 'web']);
        Permission::create(['name' => 'INGRESAR PROYECTO', 'guard_name' => 'web']);

        // Crear permisos para licitaciones
        Permission::create(['name' => 'INGRESAR ORDEN DE COMPRA', 'guard_name' => 'web']);
        Permission::create(['name' => 'MODIFICAR LICITACION', 'guard_name' => 'web']); 
        Permission::create(['name' => 'ELIMINAR LICITACION', 'guard_name' => 'web']);

        // Asignar permisos para ordenes de compras
        Permission::create(['name' => 'MODIFICAR ORDEN DE COMPRA', 'guard_name' => 'web']);
        Permission::create(['name' => 'ELIMINAR ORDEN DE COMPRA', 'guard_name' => 'web']);

        // Asignar permisos al menú
        Permission::create(['name' => 'MENU PERSONAL AUTORIZADO', 'guard_name' => 'web']);
        Permission::create(['name' => 'MENU USUARIOS DEL SISTEMA', 'guard_name' => 'web']);
        Permission::create(['name' => 'MENU ROLES', 'guard_name' => 'web']);
        Permission::create(['name' => 'MENU PERMISOS', 'guard_name' => 'web']);
        Permission::create(['name' => 'MENU CONFIGURACION', 'guard_name' => 'web']);
        Permission::create(['name' => 'MENU PLAN ANUAL DE COMPRAS', 'guard_name' => 'web']);
        Permission::create(['name' => 'MENU LICITACIONES', 'guard_name' => 'web']);
        Permission::create(['name' => 'MENU ORDENES DE COMPRA', 'guard_name' => 'web']);
        Permission::create(['name' => 'MENU DASHBOARD', 'guard_name' => 'web']);
        Permission::create(['name' => 'MENU REPORTES', 'guard_name' => 'web']);
        Permission::create(['name' => 'MENU BITACORA', 'guard_name' => 'web']); 
        Permission::create(['name' => 'MENU AUTENTIFICATIC', 'guard_name' => 'web']);   
        Permission::create(['name' => 'MENU PRESUPUESTO', 'guard_name' => 'web']);

        // Crear permisos para configuración
        Permission::create(['name' => 'GRADOS DEL PERSONAL', 'guard_name' => 'web']);
        Permission::create(['name' => 'DEPARTAMENTOS', 'guard_name' => 'web']);
        Permission::create(['name' => 'ESPECIES O SERVICIOS', 'guard_name' => 'web']);
        Permission::create(['name' => 'CLASIFICADOR', 'guard_name' => 'web']);
        Permission::create(['name' => 'DESCRIPCION CLASIFICADOR', 'guard_name' => 'web']);
        Permission::create(['name' => 'UNIDAD DE COMPRAS', 'guard_name' => 'web']);
        Permission::create(['name' => 'ESTADO DEL REGISTRO', 'guard_name' => 'web']);
        Permission::create(['name' => 'MODALIDAD DE COMPRA', 'guard_name' => 'web']);
        Permission::create(['name' => 'ESTADO DE LICITACION', 'guard_name' => 'web']);
        Permission::create(['name' => 'ESTADO DE COMPRAS', 'guard_name' => 'web']);
        Permission::create(['name' => 'FUENTE DE FINANCIAMIENTO', 'guard_name' => 'web']);

// Asignar permisos al rol ADMINISTRADOR
$admin->givePermissionTo([
    'INGRESAR LICITACION',
    'MODIFICAR PROYECTO',
    'ELIMINAR PROYECTO',
    'INGRESAR PROYECTO',
    'INGRESAR ORDEN DE COMPRA',
    'MODIFICAR LICITACION',
    'ELIMINAR LICITACION',
    'MODIFICAR ORDEN DE COMPRA',
    'ELIMINAR ORDEN DE COMPRA',
    'MENU PERSONAL AUTORIZADO',
    'MENU USUARIOS DEL SISTEMA',
    'MENU ROLES',
    'MENU PERMISOS',
    'MENU CONFIGURACION',
    'MENU PLAN ANUAL DE COMPRAS',
    'MENU LICITACIONES',
    'MENU ORDENES DE COMPRA',
    'MENU DASHBOARD',
    'MENU REPORTES',
    'MENU BITACORA',
    'MENU AUTENTIFICATIC',
    'MENU PRESUPUESTO',
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
]);
}
}
