<?php

namespace App\Http\Controllers;

use App\Models\Adicion;
use Illuminate\Http\Request;

class AdicionController extends Controller
{
    public function index()
    {
        $adiciones = Adicion::all();
        return view('adiciones.index', compact('adiciones'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'precio' => 'nullable|numeric|min:0',
        ]);

        $adicion = Adicion::create($validated);

        // Retornamos JSON para que Alpine.js lo agregue dinámicamente
        return response()->json($adicion, 201);
    }

    public function update(Request $request, Adicion $adicion)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'precio' => 'nullable|numeric|min:0',
        ]);

        $adicion->update($validated);

        return response()->json($adicion);
    }

    public function destroy(Adicion $adicion)
    {
        $adicion->delete();

        return response()->json(['success' => true]);
    }
}
