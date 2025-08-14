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

Route::get('/', fn() => redirect()->route('login'));

// Si el user tiene restaurante, mándalo directo allí; si no, dashboard normal

// Landing pública simple
Route::view('/flexfood', 'landing')->name('landing');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ✅ Global, siempre segura
Route::get('/dashboard', [DashboardController::class, 'indexGlobal'])
    ->middleware(['auth','verified'])
    ->name('dashboard');

// ✅ Por restaurante (con slug)
Route::prefix('r/{restaurante:slug}')
    ->middleware(['auth'])->scopeBindings()
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('rest.dashboard');
    });

// Todo lo que crea/edita/borra (y público con slug) bajo /r/{restaurante:slug}
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

    // Mesas
    Route::get('/mesas', [MesaController::class, 'index'])->name('mesas.index');
    Route::post('/mesas/ajax-crear', [MesaController::class, 'crearAjax'])->name('mesas.crearAjax');
    Route::get('/mesas/imprimir-hoja', [MesaController::class, 'vistaImprimirHoja'])->name('mesas.imprimirHoja');

    // === Comandas / Órdenes (scopeadas por restaurante) ===
    Route::get('/comandas', [OrdenController::class, 'index'])->name('comandas.index');

    Route::get('/comandas/nuevas', [OrdenController::class, 'nuevas'])->name('comandas.nuevas');
    Route::get('/comandas/panel', [OrdenController::class, 'panel'])
        ->name('comandas.panel');

    Route::get('/comandas/{orden}', [OrdenController::class, 'show'])->name('comandas.show');
    Route::post('/comandas/{orden}/activar', [OrdenController::class, 'activar'])->name('comandas.activar');
    Route::post('/comandas/{orden}/entregar', [OrdenController::class, 'entregar'])->name('comandas.entregar');
    Route::post('/comandas/{orden}/desactivar', [OrdenController::class, 'desactivar'])->name('comandas.desactivar');


    // Cierre de mesa desde app (antes estaba global)
    Route::post('/api/finalizar', [OrdenController::class, 'finalizar'])->name('ordenes.finalizar');

    // Historial, seguimiento y estado
    Route::get('/historial-mesas', [OrdenController::class, 'historial'])->name('historial.mesas');


    // Ticket JSON
    Route::get('/ordenes/{ordenId}/ticket', [OrdenController::class, 'generarTicket'])->name('ordenes.ticket');

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
});

// PUBLIC: menú público por slug
Route::prefix('r/{restaurante:slug}')->scopeBindings()->group(function () {
    Route::get('/menu-publico', [MenuController::class, 'publico'])->name('menu.publico');

    // Endpoints usados por el público
    Route::post('/comandas/store', [OrdenController::class, 'store'])
        ->withoutMiddleware('auth')
        ->name('comandas.store');

    Route::get('/seguimiento', [OrdenController::class, 'indexseguimiento'])
        ->withoutMiddleware('auth')
        ->name('seguimiento');

    Route::get('/estado-actual/{mesa_id}', [OrdenController::class, 'estadoActual'])->name('ordenes.estadoActual');

        // Pedir cuenta (cliente)
    Route::get('/cuenta/pedir', [OrdenController::class, 'pedirCuenta'])->name('cuenta.pedir');
});

// Público por mesa (si lo mantienes separado, puedes dejar este; si no, muévelo también al grupo con slug)
Route::get('/menu-publico/{mesa_id}', [MenuController::class, 'publicoConMesa'])->name('menu.publico.mesa');



// Landing pública / admin
Route::get('/landing', [LandingPageController::class, 'show'])->name('landing.show');
Route::middleware('auth')->group(function () {
    Route::get('/landing/edit', [LandingPageController::class, 'edit'])->name('landing.edit');
    Route::get('/api/landing',  [LandingPageController::class, 'data'])->name('landing.data');
    Route::put('/api/landing',  [LandingPageController::class, 'update'])->name('landing.update');
    Route::post('/landing/upload-image', [LandingPageController::class, 'upload'])->name('landing.upload');
});
Route::post('/landing/contact', [LandingPageController::class, 'contact'])->name('landing.contact');

// Pública (si la usas)
Route::get('/sitio', [LandingPageController::class, 'show'])->name('landing.public');

 Route::resource('restaurantes', RestauranteController::class);
require __DIR__ . '/auth.php';
