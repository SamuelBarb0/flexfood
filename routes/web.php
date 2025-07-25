<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProductoController;


Route::get('/', function () {
    return redirect()->route('login');
});


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
Route::get('/mesas', [MesaController::class, 'index'])->name('mesas.index');
Route::post('/mesas/ajax-crear', [MesaController::class, 'crearAjax'])->name('mesas.crearAjax');
Route::get('/mesas/imprimir-hoja', [MesaController::class, 'vistaImprimirHoja'])->name('mesas.imprimirHoja');
Route::get('/menu-publico', [MenuController::class, 'publico'])->name('menu.publico');
Route::get('/menu-publico/{mesa_id}', [MenuController::class, 'publicoConMesa'])->name('menu.publico.mesa');


Route::resource('categorias', CategoriaController::class);
Route::resource('productos', ProductoController::class);
require __DIR__.'/auth.php';
