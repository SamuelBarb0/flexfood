<?php

namespace App\Http\Controllers;

use App\Models\{Categoria, Adicion, Restaurante};
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Restaurante $restaurante)
    {
        $categorias = Categoria::where('restaurante_id', $restaurante->id)
            ->with([
                'productos' => fn($q) => $q->where('restaurante_id', $restaurante->id),
                'adiciones' => fn($q) => $q->where('restaurante_id', $restaurante->id),
            ])->get();

        $adiciones = Adicion::where('restaurante_id', $restaurante->id)->get();

        // === Plan / límites ===
        $plan             = $restaurante->plan ?: 'legacy';
        $soloFotos        = in_array($plan, ['basic','advanced']);
        $maxProductos     = $plan === 'basic' ? 50 : null;
        $productosActuales= $categorias->sum(fn($c) => $c->productos->count());

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
        $categorias = Categoria::where('restaurante_id', $restaurante->id)
            ->with(['productos' => fn($q) => $q->where('restaurante_id', $restaurante->id)->with('adiciones')])
            ->get();

        return view('menu.menupublico', compact('categorias', 'restaurante'));
    }

    public function publicoConMesa(Restaurante $restaurante, $mesa_id)
    {
        $categorias = Categoria::where('restaurante_id', $restaurante->id)
            ->with(['productos' => fn($q) => $q->where('restaurante_id', $restaurante->id)->with('adiciones')])
            ->get();

        return view('menu.menupublico', compact('categorias', 'mesa_id', 'restaurante'));
    }
}
