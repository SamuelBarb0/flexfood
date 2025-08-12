<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Restaurante;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Restaurante $restaurante)
    {
        $users = User::where('restaurante_id', $restaurante->id)
            ->with('roles')
            ->get();

        $roles = Role::all();

        return view('users.index', compact('users', 'roles', 'restaurante'));
    }

    public function create(Restaurante $restaurante)
    {
        $roles = Role::all();
        return view('users.create', compact('roles', 'restaurante'));
    }

    public function store(Request $request, Restaurante $restaurante)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'min:6'],
            'role'     => ['required', Rule::exists('roles', 'name')],
        ]);

        $user = User::create([
            'name'           => $data['name'],
            'email'          => $data['email'],
            'password'       => bcrypt($data['password']),
            'restaurante_id' => $restaurante->id,
        ]);

        $user->assignRole($data['role']);

        return redirect()
            ->route('users.index', $restaurante)
            ->with('success', 'Usuario creado con éxito.');
    }

    public function edit(Restaurante $restaurante, User $user)
    {
        // seguridad extra si no usas scopeBindings + relación en Restaurante
        abort_unless($user->restaurante_id === $restaurante->id, 404);

        $roles = Role::all();
        return view('users.edit', compact('user', 'roles', 'restaurante'));
    }

    public function update(Request $request, Restaurante $restaurante, User $user)
    {
        abort_unless($user->restaurante_id === $restaurante->id, 404);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'min:6'],
            'role'     => ['required', Rule::exists('roles', 'name')],
        ]);

        $user->name  = $data['name'];
        $user->email = $data['email'];
        if (!empty($data['password'])) {
            $user->password = bcrypt($data['password']);
        }
        $user->save();

        $user->syncRoles([$data['role']]);

        return redirect()
            ->route('users.index', $restaurante)
            ->with('success', 'Usuario actualizado.');
    }

    public function destroy(Restaurante $restaurante, User $user)
    {
        abort_unless($user->restaurante_id === $restaurante->id, 404);

        $user->delete();

        return redirect()
            ->route('users.index', $restaurante)
            ->with('success', 'Usuario eliminado.');
    }
}