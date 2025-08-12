<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Restaurante;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    // (Opcional) Si usas vistas separadas:
    public function create(Restaurante $restaurante)
    {
        return view('categorias.create', compact('restaurante'));
    }

    public function store(Request $request, Restaurante $restaurante)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        Categoria::create([
            'nombre'         => $request->nombre,
            'restaurante_id' => $restaurante->id,
        ]);

        return redirect()->route('menu.index', $restaurante)
            ->with('success', 'Categoría creada correctamente.');
    }

    // (Opcional) Si usas vistas separadas:
    public function edit(Restaurante $restaurante, Categoria $categoria)
    {
        abort_unless($categoria->restaurante_id === $restaurante->id, 403);

        return view('categorias.edit', compact('categoria', 'restaurante'));
    }

    public function update(Request $request, Restaurante $restaurante, Categoria $categoria)
    {
        abort_unless($categoria->restaurante_id === $restaurante->id, 403);

        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $categoria->update([
            'nombre' => $request->nombre,
        ]);

        return redirect()->route('menu.index', $restaurante)
            ->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(Restaurante $restaurante, Categoria $categoria)
    {
        abort_unless($categoria->restaurante_id === $restaurante->id, 403);

        $categoria->delete();

        return redirect()->route('menu.index', $restaurante)
            ->with('success', 'Categoría eliminada.');
    }
}
