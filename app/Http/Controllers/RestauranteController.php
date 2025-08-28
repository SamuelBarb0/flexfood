<?php

namespace App\Http\Controllers;

use App\Models\Restaurante;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RestauranteController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $restaurantes = Restaurante::with(['users:id,restaurante_id'])
            ->withCount('users')
            ->when(
                $q,
                fn($qry) => $qry->where(function ($w) use ($q) {
                    $w->where('nombre', 'like', "%{$q}%")
                      ->orWhere('slug', 'like', "%{$q}%");
                })
            )
            ->orderBy('nombre')
            ->paginate(12)
            ->withQueryString();

        // Colecciones para los modales
        $usersUnassigned = User::whereNull('restaurante_id')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'restaurante_id']);

        $usersAll = User::orderBy('name')
            ->get(['id', 'name', 'email', 'restaurante_id']);

        return view('restaurantes.index', compact('restaurantes', 'q', 'usersUnassigned', 'usersAll'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email', 'restaurante_id']);
        return view('restaurantes.create', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'     => ['required', 'string', 'max:150'],
            'slug'       => ['nullable', 'string', 'max:160', 'alpha_dash', Rule::unique('restaurantes', 'slug')],
            'plan'       => ['nullable', 'string', Rule::in(['basic','advanced','legacy'])], // '' o 'legacy' => NULL por mutator
            'usuarios'   => ['array'],
            'usuarios.*' => ['integer', 'exists:users,id'],
        ]);

        // Slug se autogenera en el booted() si viene null
        $restaurante = Restaurante::create([
            'nombre' => $data['nombre'],
            'slug'   => $data['slug'] ?? null,
            'plan'   => $data['plan'] ?? null, // setPlanAttribute normaliza legacy/'' a null
        ]);

        // Asignar usuarios seleccionados
        if (!empty($data['usuarios'])) {
            User::whereIn('id', $data['usuarios'])->update(['restaurante_id' => $restaurante->id]);
        }

        return redirect()->route('restaurantes.index')->with('success', 'Restaurante creado correctamente.');
    }

    public function edit(Restaurante $restaurante)
    {
        $users         = User::orderBy('name')->get(['id', 'name', 'email', 'restaurante_id']);
        $seleccionados = $restaurante->users()->pluck('id')->toArray();

        return view('restaurantes.edit', compact('restaurante', 'users', 'seleccionados'));
    }

    public function update(Request $request, Restaurante $restaurante)
    {
        $data = $request->validate([
            'nombre'     => ['required', 'string', 'max:150'],
            'slug'       => ['nullable', 'string', 'max:160', 'alpha_dash', Rule::unique('restaurantes', 'slug')->ignore($restaurante->id)],
            'plan'       => ['nullable', 'string', Rule::in(['basic','advanced','legacy'])], // '' o 'legacy' => NULL por mutator
            'usuarios'   => ['array'],
            'usuarios.*' => ['integer', 'exists:users,id'],
        ]);

        // Actualiza nombre/slug
        $restaurante->fill([
            'nombre' => $data['nombre'],
            'slug'   => $data['slug'] ?? $restaurante->slug,
        ]);

        // Actualiza plan (el mutator normaliza legacy/'' a null)
        if (array_key_exists('plan', $data)) {
            $restaurante->plan = $data['plan'];
        }
        $restaurante->save();

        // Sincroniza usuarios (los no seleccionados se desasignan)
        $asignados = $data['usuarios'] ?? [];
        User::where('restaurante_id', $restaurante->id)
            ->whereNotIn('id', $asignados ?: [0])
            ->update(['restaurante_id' => null]);

        if ($asignados) {
            User::whereIn('id', $asignados)->update(['restaurante_id' => $restaurante->id]);
        }

        return redirect()->route('restaurantes.index')->with('success', 'Restaurante actualizado.');
    }

    public function destroy(Restaurante $restaurante)
    {
        // Desasignar usuarios antes de eliminar
        User::where('restaurante_id', $restaurante->id)->update(['restaurante_id' => null]);

        $restaurante->delete();

        return redirect()->route('restaurantes.index')->with('success', 'Restaurante eliminado.');
    }
}
