<?php

namespace App\Http\Controllers;

use App\Models\Adicion;
use Illuminate\Http\Request;

class AdicionController extends Controller
{
    public function index()
    {
        $adiciones = Adicion::with('categorias')->get();
        return view('adiciones.index', compact('adiciones'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'precio' => 'nullable|numeric|min:0',
            'categoria_id' => 'required|array',
            'categoria_id.*' => 'exists:categorias,id',
        ]);

        $adicion = Adicion::create([
            'nombre' => $validated['nombre'],
            'precio' => $validated['precio'],
        ]);

        // Sincronizar categorías
        $adicion->categorias()->sync($validated['categoria_id']);

        return response()->json($adicion->load('categorias'), 201);
    }


    public function update(Request $request, Adicion $adicion)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'precio' => 'nullable|numeric|min:0',
            'categoria_id' => 'required|array',
            'categoria_id.*' => 'exists:categorias,id',
        ]);

        $adicion->update([
            'nombre' => $validated['nombre'],
            'precio' => $validated['precio'],
        ]);

        // Actualizar categorías
        $adicion->categorias()->sync($validated['categoria_id']);

        return response()->json($adicion->load('categorias'));
    }

    public function destroy(Adicion $adicion)
    {
        $adicion->delete();

        return response()->json(['success' => true]);
    }
}
