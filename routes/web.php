<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\RestauranteController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\AdicionController;
use App\Http\Controllers\OrdenController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\LandingPageController;
use App\Models\Restaurante;
use App\Models\Categoria;

/**
 * ==========================================
 * HOME → Landing pública en la raíz del sitio
 * ==========================================
 */
Route::get('/', [LandingPageController::class, 'show'])->name('landing.show');

// Redirección legacy (por si existen enlaces antiguos)
Route::get('/landing', fn() => redirect()->route('landing.show'));

/**
 * ==========================
 * Términos y Condiciones
 * ==========================
 */
Route::get('/terminos', [LandingPageController::class, 'terms'])->name('terminos');
Route::get('/terminos/editar', [LandingPageController::class, 'termsEdit'])->name('terminos.edit');
Route::put('/terminos', [LandingPageController::class, 'termsUpdate'])->name('terminos.update');
Route::post('/terminos/upload', [LandingPageController::class, 'termsUpload'])->name('terminos.upload');

/**
 * =======================
 * Perfil (área autenticada)
 * =======================
 */
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/**
 * ==========================
 * Dashboard general (autenticado)
 * ==========================
 */
Route::get('/dashboard', [DashboardController::class, 'indexGlobal'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

/**
 * ========================================
 * Rutas por restaurante (con slug + auth)
 * ========================================
 */
Route::prefix('r/{restaurante:slug}')
    ->middleware(['auth'])
    ->scopeBindings()
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('rest.dashboard');
    });

/**
 * ==========================================
 * Todo lo privado bajo /r/{restaurante:slug}
 * ==========================================
 */
Route::prefix('r/{restaurante:slug}')->middleware('auth')->scopeBindings()->group(function () {

    // Menú (panel)
    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');

    // Adiciones
    Route::get('/adiciones', [AdicionController::class, 'index'])->name('adiciones.index');
    Route::post('/adiciones', [AdicionController::class, 'store'])->name('adiciones.store');
    Route::put('/adiciones/{adicion}', [AdicionController::class, 'update'])->name('adiciones.update');
    Route::delete('/adiciones/{adicion}', [AdicionController::class, 'destroy'])->name('adiciones.destroy');

    // Categorías
    Route::resource('categorias', CategoriaController::class);

    // Productos
    Route::resource('productos', ProductoController::class);

    // Usuarios (trabajadores)
    Route::resource('users', UserController::class);

    // API categorías (solo de ese restaurante)
    Route::get('/api/categorias', function (Restaurante $restaurante) {
        return Categoria::where('restaurante_id', $restaurante->id)
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();
    })->name('api.categorias');

    // Analíticas
    Route::get('/analiticas', [DashboardController::class, 'analiticas'])->name('analiticas.index');

    // Zonas
    Route::get('/zonas', [App\Http\Controllers\ZonaController::class, 'index'])->name('zonas.index');
    Route::post('/zonas', [App\Http\Controllers\ZonaController::class, 'store'])->name('zonas.store');
    Route::put('/zonas/{zona}', [App\Http\Controllers\ZonaController::class, 'update'])->name('zonas.update');
    Route::delete('/zonas/{zona}', [App\Http\Controllers\ZonaController::class, 'destroy'])->name('zonas.destroy');
    Route::post('/zonas/{zona}/asignar-mesas', [App\Http\Controllers\ZonaController::class, 'asignarMesas'])->name('zonas.asignarMesas');

    // Mesas
    Route::get('/mesas', [MesaController::class, 'index'])->name('mesas.index');
    Route::post('/mesas/ajax-crear', [MesaController::class, 'crearAjax'])->name('mesas.crearAjax');
    Route::get('/mesas/imprimir-hoja', [MesaController::class, 'vistaImprimirHoja'])->name('mesas.imprimirHoja');
    Route::post('/mesas/fusionar', [MesaController::class, 'fusionar'])->name('mesas.fusionar');
    Route::post('/mesas/{mesa}/desfusionar', [MesaController::class, 'desfusionar'])->name('mesas.desfusionar');

    // Comandas / Órdenes
    Route::get('/comandas', [OrdenController::class, 'index'])->name('comandas.index');
    Route::get('/comandas/nuevas', [OrdenController::class, 'nuevas'])->name('comandas.nuevas');
    Route::get('/comandas/panel', [OrdenController::class, 'panel'])->name('comandas.panel');

    Route::get('/comandas/{orden}', [OrdenController::class, 'show'])->name('comandas.show');
    Route::post('/comandas/{orden}/activar', [OrdenController::class, 'activar'])->name('comandas.activar');
    Route::post('/comandas/{orden}/entregar', [OrdenController::class, 'entregar'])->name('comandas.entregar');
    Route::post('/comandas/{orden}/desactivar', [OrdenController::class, 'desactivar'])->name('comandas.desactivar');

    // Cierre de mesa desde app
    Route::post('/api/finalizar', [OrdenController::class, 'finalizar'])->name('ordenes.finalizar');

    // Traspasar orden a otra mesa
    Route::post('/ordenes/{orden}/traspasar', [OrdenController::class, 'traspasar'])->name('ordenes.traspasar');

    // Historial, seguimiento y estado
    Route::get('/historial-mesas', [OrdenController::class, 'historial'])->name('historial.mesas');

    // Ticket JSON
    Route::get('/ordenes/{ordenId}/ticket', [OrdenController::class, 'generarTicket'])->name('ordenes.ticket');
    // Datos frescos para TPV
    Route::get('/ordenes/{ordenId}/datos-frescos', [OrdenController::class, 'datosFrescos'])->name('ordenes.datosFrescos');

    // Gestión de pagos parciales y eliminación de productos
    Route::post('/ordenes/{orden}/marcar-pagados', [OrdenController::class, 'marcarProductosPagados'])->name('ordenes.marcarPagados');
    Route::delete('/ordenes/{orden}/eliminar-productos', [OrdenController::class, 'eliminarProductos'])->name('ordenes.eliminarProductos');

    // Settings
    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
});

/**
 * ===========================================
 * Público (por slug) — sin auth
 * ===========================================
 */
Route::prefix('r/{restaurante:slug}')->scopeBindings()->group(function () {
    Route::get('/menu-publico', [MenuController::class, 'publico'])->name('menu.publico');

    // Endpoints usados por el público
    Route::post('/comandas/store', [OrdenController::class, 'store'])
        ->withoutMiddleware('auth')
        ->name('comandas.store');

    Route::get('/seguimiento', [OrdenController::class, 'indexseguimiento'])
        ->withoutMiddleware('auth')
        ->name('seguimiento');

    Route::get('/estado-actual/{mesa_id}', [OrdenController::class, 'estadoActual'])
        ->withoutMiddleware('auth')
        ->name('ordenes.estadoActual');

    // === NUEVOS ===
    Route::get('/pedidos-entregados', [OrdenController::class, 'entregadas'])
        ->withoutMiddleware('auth')
        ->name('comandas.entregadas');

    Route::get('/pedidos-entregados/{mesa_id}', [OrdenController::class, 'entregadas'])
        ->whereNumber('mesa_id')
        ->withoutMiddleware('auth')
        ->name('comandas.entregadas.path');

    // Pedir cuenta (pone a estado=3 las comandas entregadas de la mesa)
    // Mantengo tu ruta, ahora también sin auth por ser público
    Route::get('/cuenta/pedir', [OrdenController::class, 'pedirCuenta'])
        ->withoutMiddleware('auth')
        ->name('cuenta.pedir');

    Route::get('/comandas/{orden}/estado', [OrdenController::class, 'estadoOrden'])
        ->whereNumber('orden')
        ->withoutMiddleware('auth')
        ->name('comandas.estado');

    // routes/web.php
    Route::get('/cuenta/pedir-pedido', [OrdenController::class, 'pedirCuentaPedido'])
        ->withoutMiddleware('auth')
        ->name('cuenta.pedirPedido');
});


// Público por mesa (ruta directa por ID de mesa)
Route::get('/menu-publico/{mesa_id}', [MenuController::class, 'publicoConMesa'])->name('menu.publico.mesa');

/**
 * ==========================================
 * Tickets (correo)
 * ==========================================
 */
Route::post('/tickets/{orden}/enviar-email', [OrdenController::class, 'enviarEmail'])
    ->name('tickets.enviarEmail');

/**
 * ==========================================
 * Landing: edición / API / subida (autenticado)
 * ==========================================
 */
Route::middleware('auth')->group(function () {
    Route::get('/landing/edit', [LandingPageController::class, 'edit'])->name('landing.edit');
    Route::get('/api/landing',  [LandingPageController::class, 'data'])->name('landing.data');
    Route::put('/api/landing',  [LandingPageController::class, 'update'])->name('landing.update');
    Route::post('/landing/upload-image', [LandingPageController::class, 'upload'])->name('landing.upload');
});

// Formulario de contacto de la landing (público)
Route::post('/landing/contact', [LandingPageController::class, 'contact'])->name('landing.contact');

// (Opcional) alias público
Route::get('/sitio', [LandingPageController::class, 'show'])->name('landing.public');

/**
 * ==========================================
 * Restaurantes (resource)
 * ==========================================
 */
Route::resource('restaurantes', RestauranteController::class);

/**
 * ==========================================
 * Auth scaffolding
 * ==========================================
 */
require __DIR__ . '/auth.php';
