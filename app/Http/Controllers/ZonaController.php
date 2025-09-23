<?php

namespace App\Http\Controllers;

use App\Models\Zona;
use App\Models\Restaurante;
use Illuminate\Http\Request;

class ZonaController extends Controller
{
    public function index(Restaurante $restaurante)
    {
        $zonas = Zona::where('restaurante_id', $restaurante->id)
            ->orderBy('orden')
            ->with('mesas')
            ->get();

        return response()->json($zonas);
    }

    public function store(Request $request, Restaurante $restaurante)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'orden' => 'nullable|integer|min:0',
        ]);

        $validated['restaurante_id'] = $restaurante->id;

        if (!isset($validated['orden'])) {
            $validated['orden'] = Zona::where('restaurante_id', $restaurante->id)->max('orden') + 1;
        }

        $zona = Zona::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'zona' => $zona->load('mesas'),
                'message' => 'Zona creada exitosamente.'
            ]);
        }

        return redirect()->back()->with('success', 'Zona creada exitosamente.');
    }

    public function update(Request $request, Restaurante $restaurante, Zona $zona)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'orden' => 'nullable|integer|min:0',
        ]);

        $zona->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'zona' => $zona->load('mesas'),
                'message' => 'Zona actualizada exitosamente.'
            ]);
        }

        return redirect()->back()->with('success', 'Zona actualizada exitosamente.');
    }

    public function destroy(Request $request, Restaurante $restaurante, Zona $zona)
    {
        // Desvincular mesas de esta zona
        $zona->mesas()->update(['zona_id' => null]);

        $zona->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Zona eliminada exitosamente.'
            ]);
        }

        return redirect()->back()->with('success', 'Zona eliminada exitosamente.');
    }

    public function asignarMesas(Request $request, Restaurante $restaurante, Zona $zona)
    {
        $validated = $request->validate([
            'mesa_ids' => 'array',
            'mesa_ids.*' => 'integer|exists:mesas,id',
        ]);

        $mesaIds = $validated['mesa_ids'] ?? [];

        // Primero desasignar todas las mesas de esta zona
        \App\Models\Mesa::where('zona_id', $zona->id)
            ->where('restaurante_id', $restaurante->id)
            ->update(['zona_id' => null]);

        if (!empty($mesaIds)) {
            // Verificar que todas las mesas pertenecen al restaurante
            $mesas = \App\Models\Mesa::whereIn('id', $mesaIds)
                ->where('restaurante_id', $restaurante->id)
                ->get();

            if ($mesas->count() !== count($mesaIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Algunas mesas no pertenecen a este restaurante.'
                ], 400);
            }

            // Asignar las mesas seleccionadas a esta zona
            \App\Models\Mesa::whereIn('id', $mesaIds)
                ->update(['zona_id' => $zona->id]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => count($mesaIds) > 0
                    ? 'Mesas asignadas exitosamente a la zona.'
                    : 'Mesas desasignadas de la zona.',
                'mesas_asignadas' => count($mesaIds)
            ]);
        }

        return redirect()->back()->with('success', 'Mesas asignadas exitosamente a la zona.');
    }
}
