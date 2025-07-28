<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\AdicionController;
use App\Http\Controllers\OrdenController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return redirect()->route('login');
});


Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
Route::get('/adiciones', [AdicionController::class, 'index'])->name('adiciones.index');
Route::post('/adiciones', [AdicionController::class, 'store'])->name('adiciones.store');
Route::put('/adiciones/{adicion}', [AdicionController::class, 'update'])->name('adiciones.update');
Route::delete('/adiciones/{adicion}', [AdicionController::class, 'destroy'])->name('adiciones.destroy');

Route::get('/mesas', [MesaController::class, 'index'])->name('mesas.index');
Route::post('/mesas/ajax-crear', [MesaController::class, 'crearAjax'])->name('mesas.crearAjax');
Route::get('/mesas/imprimir-hoja', [MesaController::class, 'vistaImprimirHoja'])->name('mesas.imprimirHoja');
Route::get('/menu-publico', [MenuController::class, 'publico'])->name('menu.publico');
Route::get('/menu-publico/{mesa_id}', [MenuController::class, 'publicoConMesa'])->name('menu.publico.mesa');

Route::get('/api/categorias', function () {
    return \App\Models\Categoria::select('id', 'nombre')->get();
});

Route::get('/comandas', [OrdenController::class, 'index'])->name('comandas.index');

Route::post('/comandas/store', [OrdenController::class, 'store'])->name('comandas.store');

Route::get('/comandas/{orden}', [OrdenController::class, 'show'])->name('comandas.show');

Route::post('/comandas/{orden}/activar', [OrdenController::class, 'activar'])->name('comandas.activar');

Route::post('/comandas/{orden}/entregar', [OrdenController::class, 'entregar'])->name('comandas.entregar');

Route::post('/comandas/{orden}/desactivar', [OrdenController::class, 'desactivar'])->name('comandas.desactivar');

Route::post('/api/finalizar', [OrdenController::class, 'finalizar']);

Route::resource('categorias', CategoriaController::class);
Route::resource('productos', ProductoController::class);
require __DIR__ . '/auth.php';
