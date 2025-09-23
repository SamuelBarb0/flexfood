<?php

namespace App\Http\Controllers;

use App\Models\{Categoria, Adicion, Restaurante};
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Restaurante $restaurante)
    {
        $categorias = Categoria::where('restaurante_id', $restaurante->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->with([
                'productos' => fn($q) => $q->where('restaurante_id', $restaurante->id),
                'adiciones' => fn($q) => $q->where('restaurante_id', $restaurante->id),
            ])->get();

        $adiciones = Adicion::where('restaurante_id', $restaurante->id)->get();

        // === Plan / límites ===
        $plan             = $restaurante->plan ?: 'legacy';
        $soloFotos        = in_array($plan, ['basic', 'advanced']);
        $maxProductos     = $plan === 'basic' ? 50 : null;
        $productosActuales = $categorias->sum(fn($c) => $c->productos->count());

        return view('menu.index', compact(
            'categorias',
            'adiciones',
            'restaurante',
            'soloFotos',
            'maxProductos',
            'productosActuales'
        ));
    }

    public function publico(Restaurante $restaurante)
    {
        // Para que en Blade puedas verificar si el usuario pertenece al restaurante
        $restaurante->loadMissing(['users:id,restaurante_id']);

        // Categorías + productos (solo del restaurante) + adiciones
        $categorias = $restaurante->categorias()
            ->orderBy('orden')
            ->orderBy('nombre')
            ->with(['productos' => function ($q) use ($restaurante) {
                $q->where('restaurante_id', $restaurante->id)
                    // ->where('disponible', true) // ← descomenta si quieres filtrar aquí
                    ->with(['adiciones:id,nombre,precio']); // columnas útiles
            }])
            ->get(['id', 'nombre', 'restaurante_id']);

        // Mesas del restaurante para el drawer
        $mesas = $restaurante->mesas()
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo_qr', 'restaurante_id']);

        return view('menu.menupublico', compact('categorias', 'restaurante', 'mesas'));
    }

    public function publicoConMesa(Restaurante $restaurante, $mesa_id)
    {
        $categorias = Categoria::where('restaurante_id', $restaurante->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->with(['productos' => fn($q) => $q->where('restaurante_id', $restaurante->id)->with('adiciones')])
            ->get();

        return view('menu.menupublico', compact('categorias', 'mesa_id', 'restaurante'));
    }
}
