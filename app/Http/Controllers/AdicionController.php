<?php

namespace App\Http\Controllers;

use App\Models\{Adicion, Categoria, Restaurante};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdicionController extends Controller
{
    public function index(Restaurante $restaurante)
    {
        $adiciones = Adicion::where('restaurante_id', $restaurante->id)
            ->with(['categorias:id,nombre'])
            ->get();

        return view('adiciones.index', compact('adiciones', 'restaurante'));
    }

    public function store(Request $request, Restaurante $restaurante)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'precio' => 'nullable|numeric|min:0',
            'categoria_id'   => ['required','array'],
            'categoria_id.*' => [
                Rule::exists('categorias','id')->where('restaurante_id', $restaurante->id),
            ],
        ]);

        $adicion = Adicion::create([
            'nombre'         => $validated['nombre'],
            'precio'         => $validated['precio'] ?? null,
            'restaurante_id' => $restaurante->id,
        ]);

        $catIds = Categoria::where('restaurante_id', $restaurante->id)
            ->whereIn('id', $validated['categoria_id'])
            ->pluck('id');

        $adicion->categorias()->sync($catIds);

        return response()->json($adicion->load('categorias'), 201);
    }

    public function update(Request $request, Restaurante $restaurante, Adicion $adicion)
    {
        abort_unless($adicion->restaurante_id === $restaurante->id, 403);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'precio' => 'nullable|numeric|min:0',
            'categoria_id'   => ['required','array'],
            'categoria_id.*' => [
                Rule::exists('categorias','id')->where('restaurante_id', $restaurante->id),
            ],
        ]);

        $adicion->update([
            'nombre' => $validated['nombre'],
            'precio' => $validated['precio'] ?? null,
        ]);

        $catIds = Categoria::where('restaurante_id', $restaurante->id)
            ->whereIn('id', $validated['categoria_id'])
            ->pluck('id');

        $adicion->categorias()->sync($catIds);

        return response()->json($adicion->load('categorias'));
    }

    public function destroy(Restaurante $restaurante, Adicion $adicion)
    {
        abort_unless($adicion->restaurante_id === $restaurante->id, 403);
        $adicion->delete();
        return response()->json(['success' => true]);
    }
}
