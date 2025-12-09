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
            'plan'       => ['nullable', 'string', Rule::in(['basic','advanced','legacy'])],
            'usuarios'   => ['array'],
            'usuarios.*' => ['integer', 'exists:users,id'],
            // Datos fiscales
            'razon_social'     => ['nullable', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'nif'              => ['nullable', 'string', 'max:9'],
            'direccion_fiscal' => ['nullable', 'string', 'max:255'],
            'municipio'        => ['nullable', 'string', 'max:100'],
            'provincia'        => ['nullable', 'string', 'max:100'],
            'codigo_postal'    => ['nullable', 'string', 'max:10'],
            'pais'             => ['nullable', 'string', 'max:100'],
            'regimen_iva'      => ['nullable', 'string', Rule::in(['general', 'simplificado', 'criterio_caja'])],
            'epigrafe_iae'     => ['nullable', 'string', 'max:50'],
            'email_fiscal'     => ['nullable', 'email', 'max:255'],
            'telefono_fiscal'  => ['nullable', 'string', 'max:20'],
        ]);

        // Slug se autogenera en el booted() si viene null
        $restaurante = Restaurante::create([
            'nombre'           => $data['nombre'],
            'slug'             => $data['slug'] ?? null,
            'plan'             => $data['plan'] ?? null,
            // Datos fiscales
            'razon_social'     => $data['razon_social'] ?? null,
            'nombre_comercial' => $data['nombre_comercial'] ?? null,
            'nif'              => $data['nif'] ?? null,
            'direccion_fiscal' => $data['direccion_fiscal'] ?? null,
            'municipio'        => $data['municipio'] ?? null,
            'provincia'        => $data['provincia'] ?? null,
            'codigo_postal'    => $data['codigo_postal'] ?? null,
            'pais'             => $data['pais'] ?? 'España',
            'regimen_iva'      => $data['regimen_iva'] ?? null,
            'epigrafe_iae'     => $data['epigrafe_iae'] ?? null,
            'email_fiscal'     => $data['email_fiscal'] ?? null,
            'telefono_fiscal'  => $data['telefono_fiscal'] ?? null,
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
            'plan'       => ['nullable', 'string', Rule::in(['basic','advanced','legacy'])],
            'usuarios'   => ['array'],
            'usuarios.*' => ['integer', 'exists:users,id'],
            // Datos fiscales
            'razon_social'     => ['nullable', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'nif'              => ['nullable', 'string', 'max:9'],
            'direccion_fiscal' => ['nullable', 'string', 'max:255'],
            'municipio'        => ['nullable', 'string', 'max:100'],
            'provincia'        => ['nullable', 'string', 'max:100'],
            'codigo_postal'    => ['nullable', 'string', 'max:10'],
            'pais'             => ['nullable', 'string', 'max:100'],
            'regimen_iva'      => ['nullable', 'string', Rule::in(['general', 'simplificado', 'criterio_caja'])],
            'epigrafe_iae'     => ['nullable', 'string', 'max:50'],
            'email_fiscal'     => ['nullable', 'email', 'max:255'],
            'telefono_fiscal'  => ['nullable', 'string', 'max:20'],
        ]);

        // Actualiza todos los campos
        $restaurante->fill([
            'nombre'           => $data['nombre'],
            'slug'             => $data['slug'] ?? $restaurante->slug,
            // Datos fiscales
            'razon_social'     => $data['razon_social'] ?? null,
            'nombre_comercial' => $data['nombre_comercial'] ?? null,
            'nif'              => $data['nif'] ?? null,
            'direccion_fiscal' => $data['direccion_fiscal'] ?? null,
            'municipio'        => $data['municipio'] ?? null,
            'provincia'        => $data['provincia'] ?? null,
            'codigo_postal'    => $data['codigo_postal'] ?? null,
            'pais'             => $data['pais'] ?? 'España',
            'regimen_iva'      => $data['regimen_iva'] ?? null,
            'epigrafe_iae'     => $data['epigrafe_iae'] ?? null,
            'email_fiscal'     => $data['email_fiscal'] ?? null,
            'telefono_fiscal'  => $data['telefono_fiscal'] ?? null,
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
