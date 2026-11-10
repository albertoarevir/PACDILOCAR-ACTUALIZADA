<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\EstadoModificacionController;
use App\Http\Controllers\FuenteFinanciamientoController;
use App\Http\Controllers\ApiLoginController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\PresupuestoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ReporteDashboardController;


Route::get('/', function () {
    return redirect()->route('login.custom');
});


Route::get('/', [ApiLoginController::class, 'showLoginForm'])->name('login.custom');
Route::post('/login-api-process', [ApiLoginController::class, 'loginWithApi'])->name('login.api')->middleware(['throttle:5,1', 'throttle:login-rut']);
Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout')->middleware('auth');


Route::middleware(['auth'])->group(function () {
    Route::get('/get-especies', [\App\Http\Controllers\PacController::class, 'getEspecies'])->name('get-especies');
    Route::get('/get-codigos',  [App\Http\Controllers\PacController::class,  'getCodigos'])->name('get-codigos');
    Route::get('/get-presupuesto-sap', [\App\Http\Controllers\PacController::class, 'getPresupuestoSap'])->name('get-presupuesto-sap');
});


Route::middleware(['auth'])->group(function () {
    Route::get('/admin', [App\Http\Controllers\AdminController::class, 'index'])->name('admin.index');
});


Route::middleware(['auth', 'role:ADMINISTRADOR'])->group(function () {
    Route::get('/admin/usuarios',                 [App\Http\Controllers\UsuarioController::class, 'index'])->name('admin.usuarios.index');
    Route::get('/admin/usuarios/create',          [App\Http\Controllers\UsuarioController::class, 'create'])->name('admin.usuarios.create');
    Route::post('/admin/usuarios/create',         [App\Http\Controllers\UsuarioController::class, 'store'])->name('admin.usuarios.store');
    Route::get('/admin/usuarios/{id}',            [App\Http\Controllers\UsuarioController::class, 'show'])->name('admin.usuarios.show');
    Route::get('/admin/usuarios/{id}/edit',       [App\Http\Controllers\UsuarioController::class, 'edit'])->name('admin.usuarios.edit');
    Route::put('/admin/usuarios/{id}',            [App\Http\Controllers\UsuarioController::class, 'update'])->name('admin.usuarios.update');
    Route::get('/admin/usuarios/{id}/confirm-delete', [App\Http\Controllers\UsuarioController::class, 'confirmDelete'])->name('admin.usuarios.confirmDelete');
    Route::delete('/admin/usuarios/{id}',         [App\Http\Controllers\UsuarioController::class, 'destroy'])->name('admin.usuarios.destroy');
    Route::patch('/admin/usuarios/{id}/habilitado', [App\Http\Controllers\UsuarioController::class, 'toggleHabilitado'])->name('admin.usuarios.toggleHabilitado');

    Route::resource('/asignarRol', \App\Http\Controllers\AsignarController::class)->names('asignar');
    Route::resource('/roles',   \App\Http\Controllers\RoleController::class)->names('roles');
    Route::post('/roles',       [App\Http\Controllers\RoleController::class,  'store'])->name('roles.store');
    Route::get('/roles/create', [\App\Http\Controllers\RoleController::class, 'create'])->name('roles.create');

    Route::resource('/permisos',   \App\Http\Controllers\PermisoController::class)->names('permisos');
    Route::post('/permisos',       [App\Http\Controllers\PermisoController::class,  'store'])->name('permisos.store');
    Route::get('/permisos/create', [\App\Http\Controllers\PermisoController::class, 'create'])->name('permisos.create');

});

// --- BITACORA -------------------------------------------------------------------
Route::middleware(['auth', 'permission:MENU BITACORA'])->group(function () {
    Route::get('/bitacora', [BitacoraController::class, 'index'])->name('bitacora.index');
});

Route::get('/debug-permisos', function () {
    $configPermisos = [
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

    $adminRole = \Spatie\Permission\Models\Role::where('name', 'ADMINISTRADOR')->first();

    foreach ($configPermisos as $pName) {
        $p = \Spatie\Permission\Models\Permission::firstOrCreate(
            ['name' => $pName, 'guard_name' => 'web']
        );
        if ($adminRole && !$adminRole->hasPermissionTo($pName)) {
            $adminRole->givePermissionTo($p);
        }
    }

    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    return response()->json([
        'status' => 'success',
        'all_permissions' => \Spatie\Permission\Models\Permission::pluck('name'),
    ]);
});

// AUTENTIFICATIC 
Route::middleware(['auth', 'permission:MENU AUTENTIFICATIC'])->group(function () {
    Route::get('/Autenti/registerUser',  [App\Http\Controllers\Autenti\AutentiController::class, 'index'])->name('registerUser.form');
    Route::post('/Autenti/registerUser', [App\Http\Controllers\Autenti\AutentiController::class, 'store'])->name('registerUser.store');
    Route::get('/Autenti/funcionarios',  [App\Http\Controllers\Autenti\AutentiController::class, 'listado'])->name('autenti.funcionarios');
    Route::post('/Autenti/sync/{rut}',   [App\Http\Controllers\Autenti\AutentiController::class, 'syncFuncionario'])->name('autenti.sync');
    Route::post('/Autenti/funcionario/{rut}/dotacion', [App\Http\Controllers\Autenti\AutentiController::class, 'updateDotacion'])->name('autenti.updateDotacion');
    Route::delete('/Autenti/usuario/{rut}', [App\Http\Controllers\Autenti\AutentiController::class, 'eliminarUsuario'])->name('autenti.eliminarUsuario');
    Route::post('/Autenti/eliminarUsuario',  [App\Http\Controllers\Autenti\AutentiController::class, 'eliminarUsuario'])->name('autenti.eliminarUsuario.post');
});

// PLAN ANUAL DE COMPRAS 
Route::middleware(['auth', 'permission:MENU PLAN ANUAL DE COMPRAS'])->group(function () {
    Route::get('/pac', [App\Http\Controllers\PacController::class, 'index'])->name('pac.index');
});

Route::middleware(['auth', 'permission:INGRESAR PROYECTO'])->group(function () {
    Route::get('/pac/create', [App\Http\Controllers\PacController::class, 'create'])->name('pac.create');
    Route::post('/pac',       [App\Http\Controllers\PacController::class, 'store'])->name('pac.store');
});

Route::middleware(['auth', 'permission:MODIFICAR PROYECTO'])->group(function () {
    Route::get('/pac/{id}/edit', [App\Http\Controllers\PacController::class, 'edit'])->name('pac.edit');
    Route::put('/pac/{id}',      [App\Http\Controllers\PacController::class, 'update'])->name('pac.update');
});

Route::middleware(['auth', 'permission:ELIMINAR PROYECTO'])->group(function () {
    Route::delete('/pac/{id}', [\App\Http\Controllers\PacController::class, 'destroy'])->name('destroy');
});

//LICITACIONES
Route::middleware(['auth', 'permission:MENU LICITACIONES'])->group(function () {
    Route::get('/modalidad', [\App\Http\Controllers\ModalidadController::class, 'index'])->name('modalidad.index');
});

Route::middleware(['auth', 'permission:INGRESAR LICITACION'])->group(function () {
    Route::get('/modalidad/create/{pac}/{id_mod?}', [\App\Http\Controllers\ModalidadController::class, 'create'])->name('modalidad.create');
    Route::post('/modalidad',                       [\App\Http\Controllers\ModalidadController::class, 'store'])->name('modalidad.store');
});

Route::middleware(['auth', 'permission:MODIFICAR LICITACION'])->group(function () {
    Route::get('/modalidad/{id}/edit', [\App\Http\Controllers\ModalidadController::class, 'edit'])->name('modalidad.edit');
    Route::put('/modalidad/{id}',      [\App\Http\Controllers\ModalidadController::class, 'update'])->name('modalidad.update');
});

Route::middleware(['auth', 'permission:ELIMINAR LICITACION'])->group(function () {
    Route::delete('/modalidad/{id}', [\App\Http\Controllers\ModalidadController::class, 'destroy'])->name('modalidad.destroy');
});

//ORDENES DE COMPRA
Route::middleware(['auth', 'permission:MENU ORDENES DE COMPRA'])->group(function () {
    Route::get('/ordenes', [\App\Http\Controllers\OrdenController::class, 'index'])->name('ordenes.index');
});

Route::middleware(['auth', 'permission:INGRESAR ORDEN DE COMPRA'])->group(function () {
    Route::get('/ordenes/create/{pac}/{modalidad}/{numero}/{id_mod}', [\App\Http\Controllers\OrdenController::class, 'create'])->name('ordenes.create');
    Route::post('/ordenes/store/{pac_id}/{id_mod}',                   [\App\Http\Controllers\OrdenController::class, 'store'])->name('ordenes.store');
});

Route::middleware(['auth', 'permission:MODIFICAR ORDEN DE COMPRA'])->group(function () {
    Route::get('/ordenes/{id}/edit', [\App\Http\Controllers\OrdenController::class, 'edit'])->name('ordenes.edit');
    Route::put('/ordenes/{ordenes}', [\App\Http\Controllers\OrdenController::class, 'update'])->name('ordenes.update');
    Route::post('/ordenes/{id}/pago-factura', [\App\Http\Controllers\OrdenController::class, 'guardarFacturaAjax'])->name('ordenes.guardarFacturaAjax');
    Route::put('/ordenes/pago-factura/{id_pago}', [\App\Http\Controllers\OrdenController::class, 'actualizarFacturaAjax'])->name('ordenes.actualizarFacturaAjax');
    Route::delete('/ordenes/pago-factura/{id_pago}', [\App\Http\Controllers\OrdenController::class, 'eliminarFacturaAjax'])->name('ordenes.eliminarFacturaAjax');
});

Route::middleware(['auth', 'permission:ELIMINAR LICITACION'])->group(function () {
    Route::delete('/ordenes/{id}', [\App\Http\Controllers\OrdenController::class, 'destroy'])->name('ordenes.destroy');
});

//PRESUPUESTO
Route::middleware(['auth', 'permission:MENU PRESUPUESTO'])->group(function () {
    Route::get('/presupuesto/check-duplicate', [\App\Http\Controllers\PresupuestoController::class, 'checkDuplicate'])->name('presupuesto.check-duplicate');
    Route::resource('/presupuesto', \App\Http\Controllers\PresupuestoController::class)->names('presupuesto');
});

//DASHBOARD
Route::middleware(['auth', 'permission:MENU DASHBOARD'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
});

//REPORTES
Route::middleware(['auth', 'permission:MENU REPORTES'])->group(function () {
    Route::get('/reporte',           [ReporteController::class,          'index'])->name('reporte.index');
    Route::get('/reporte/dashboard', [ReporteDashboardController::class, 'index'])->name('reporte.dashboard');
});


//CONFIGURACION 
Route::middleware(['auth'])->group(function () {
    Route::resource('/grados',              \App\Http\Controllers\GradoController::class)->middleware('permission:MENU CONFIGURACION|GRADOS DEL PERSONAL');
    Route::resource('/departamentos',       \App\Http\Controllers\DepartamentoController::class)->middleware('permission:MENU CONFIGURACION|DEPARTAMENTOS');
    Route::resource('/especies',            \App\Http\Controllers\EspecieController::class)->middleware('permission:MENU CONFIGURACION|ESPECIES O SERVICIOS');
    Route::resource('/estados',             \App\Http\Controllers\EstadoController::class)->middleware('permission:MENU CONFIGURACION|ESTADO DEL REGISTRO');
    Route::resource('/clasificador',        \App\Http\Controllers\ClasificadorController::class)->middleware('permission:MENU CONFIGURACION|CLASIFICADOR');
    Route::resource('/unidadcompra',        \App\Http\Controllers\UnidadCompraController::class)->middleware('permission:MENU CONFIGURACION|UNIDAD DE COMPRAS');
    Route::resource('/codigos',             \App\Http\Controllers\CodigoController::class)->middleware('permission:MENU CONFIGURACION|DESCRIPCION CLASIFICADOR');
    Route::resource('/tipodecompra',        \App\Http\Controllers\TipocompraController::class)->middleware('permission:MENU CONFIGURACION|MODALIDAD DE COMPRA');
    Route::resource('estados-modificacion', EstadoModificacionController::class)->middleware('permission:MENU CONFIGURACION|ESTADO DEL REGISTRO');
    Route::resource('fuentefinanciamiento', FuenteFinanciamientoController::class)->middleware('permission:MENU CONFIGURACION|FUENTE DE FINANCIAMIENTO');

    Route::middleware('permission:MENU CONFIGURACION|ESTADO DE LICITACION')->group(function () {
        Route::get('/estadolicitacion',                        [\App\Http\Controllers\EstadoLicitacionController::class, 'index'])->name('estadolicitacion.index');
        Route::get('/estadolicitacion/create',                 [\App\Http\Controllers\EstadoLicitacionController::class, 'create'])->name('estadolicitacion.create');
        Route::post('/estadolicitacion.store',                 [\App\Http\Controllers\EstadoLicitacionController::class, 'store'])->name('estadolicitacion.store');
        Route::get('/estadolicitacion/{id}/edit',              [\App\Http\Controllers\EstadoLicitacionController::class, 'edit'])->name('estadolicitacion.edit');
        Route::put('/estadolicitacion/{estadoLicitacion}',     [App\Http\Controllers\EstadoLicitacionController::class, 'update'])->name('estadolicitacion.update');
    });

    Route::middleware('permission:MENU CONFIGURACION|ESTADO DE COMPRAS')->group(function () {
        Route::get('/estadocompras',                     [\App\Http\Controllers\EstadoCompraController::class, 'index'])->name('estadocompras.index');
        Route::get('/estadocompras/create',              [\App\Http\Controllers\EstadoCompraController::class, 'create'])->name('estadocompras.create');
        Route::post('/estadocompras.store',              [\App\Http\Controllers\EstadoCompraController::class, 'store'])->name('estadocompras.store');
        Route::get('/estadocompras/{id}/edit',           [\App\Http\Controllers\EstadoCompraController::class, 'edit'])->name('estadocompras.edit');
        Route::put('/estadocompras/{estadocom}',         [App\Http\Controllers\EstadoCompraController::class, 'update'])->name('estadocompras.update');
        Route::delete('/estadocompras/{id}',             [\App\Http\Controllers\EstadoCompraController::class, 'destroy'])->name('estadocompras.destroy');
    });
});

//FUNCIONARIOS
Route::middleware(['auth', 'role:ADMINISTRADOR'])->group(function () {
    Route::resource('/funcionarios', \App\Http\Controllers\FuncionarioController::class)->names('funcionarios');
    Route::post('/funcionarios',     [App\Http\Controllers\FuncionarioController::class, 'store'])->name('funcionarios.store');
});
