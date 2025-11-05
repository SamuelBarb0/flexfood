<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Restaurante;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * SUPERADMIN: helpers
     */
    private function protectedEmail(): string
    {
        return strtolower(config('superadmin.email'));
    }

    private function isProtectedEmail(string $email): bool
    {
        return strtolower(trim($email)) === $this->protectedEmail();
    }

    private function isProtectedUser(User $user): bool
    {
        return $this->isProtectedEmail($user->email ?? '');
    }

    /**
     * Lee límites desde el plan del restaurante.
     */
    private function planFor(Restaurante $restaurante): array
    {
        $key = $restaurante->plan ?: 'legacy';

        $cfg = config('planes_restaurante');
        if (is_array($cfg) && isset($cfg[$key])) {
            return $cfg[$key];
        }

        return match ($key) {
            'basic'    => ['max_perfiles' => 3],
            'advanced' => ['max_perfiles' => null],
            default    => ['max_perfiles' => null],
        };
    }

    private function isKitchenOrCashier(string $roleName): bool
    {
        $r = strtolower($roleName);
        return in_array($r, ['cocina','cajero'], true);
    }

    private function kcCountFor(Restaurante $restaurante): int
    {
        return User::role(['cocina','cajero'])
            ->where('restaurante_id', $restaurante->id)
            ->count();
    }

    public function index(Restaurante $restaurante)
    {
        $users = User::where('restaurante_id', $restaurante->id)
            ->with('roles')
            ->get();

        $roles            = Role::all();
        $plan             = $this->planFor($restaurante);
        $maxPerfiles      = $plan['max_perfiles'] ?? null;
        $perfilesActuales = $this->kcCountFor($restaurante);

        return view('users.index', compact('users', 'roles', 'restaurante', 'maxPerfiles', 'perfilesActuales'));
    }

    public function create(Restaurante $restaurante)
    {
        $roles            = Role::all();
        $plan             = $this->planFor($restaurante);
        $maxPerfiles      = $plan['max_perfiles'] ?? null;
        $perfilesActuales = $this->kcCountFor($restaurante);

        return view('users.create', compact('roles', 'restaurante', 'maxPerfiles', 'perfilesActuales'));
    }

    public function store(Request $request, Restaurante $restaurante)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'min:6'],
            'role'     => ['required', Rule::exists('roles', 'name')],
        ]);

        $plan        = $this->planFor($restaurante);
        $maxPerfiles = $plan['max_perfiles'] ?? null;

        // SUPERADMIN: si el email es el protegido, forzamos rol administrador y omitimos cupos
        if ($this->isProtectedEmail($data['email'])) {
            $data['role'] = 'administrador';
        } else {
            // Límite de cocina/cajero
            if (!is_null($maxPerfiles) && $this->isKitchenOrCashier($data['role'])) {
                $current = $this->kcCountFor($restaurante);
                if ($current >= $maxPerfiles) {
                    return back()
                        ->with('error', "Has alcanzado el máximo de perfiles (cocina/cajero) para tu plan ({$maxPerfiles}).")
                        ->withInput();
                }
            }
        }

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
        abort_unless($user->restaurante_id === $restaurante->id, 404);

        $roles            = Role::all();
        $plan             = $this->planFor($restaurante);
        $maxPerfiles      = $plan['max_perfiles'] ?? null;
        $perfilesActuales = $this->kcCountFor($restaurante);

        return view('users.edit', compact('user', 'roles', 'restaurante', 'maxPerfiles', 'perfilesActuales'));
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

        $plan        = $this->planFor($restaurante);
        $maxPerfiles = $plan['max_perfiles'] ?? null;

        // SUPERADMIN: si es el usuario protegido, no permitimos cambiar email ni rol
        if ($this->isProtectedUser($user)) {
            $data['email'] = $user->email;           // bloquea cambio de email
            $data['role']  = 'administrador';        // fuerza rol
        } else {
            // Evita que otro usuario adopte el email protegido
            if ($this->isProtectedEmail($data['email'])) {
                return back()
                    ->withErrors(['email' => 'Este email está reservado y no puede ser asignado.'])
                    ->withInput();
            }

            // Límite cocina/cajero solo si intenta pasar a KC y antes no lo era
            $wasKC  = $user->hasRole('cocina') || $user->hasRole('cajero');
            $willKC = $this->isKitchenOrCashier($data['role']);

            if (!is_null($maxPerfiles) && $willKC && !$wasKC) {
                $current = $this->kcCountFor($restaurante);
                if ($current >= $maxPerfiles) {
                    return back()
                        ->with('error', "Has alcanzado el máximo de perfiles (cocina/cajero) para tu plan ({$maxPerfiles}).")
                        ->withInput();
                }
            }
        }

        // Datos base
        $user->name  = $data['name'];
        $user->email = $data['email'];
        if (!empty($data['password'])) {
            $user->password = bcrypt($data['password']);
        }
        $user->save();

        // Roles
        if ($this->isProtectedUser($user)) {
            $user->syncRoles(['administrador']); // asegura que se quede como admin
        } else {
            $user->syncRoles([$data['role']]);
        }

        return redirect()
            ->route('users.index', $restaurante)
            ->with('success', 'Usuario actualizado.');
    }

    public function destroy(Restaurante $restaurante, User $user)
    {
        abort_unless($user->restaurante_id === $restaurante->id, 404);

        // SUPERADMIN: no se puede eliminar
        if ($this->isProtectedUser($user)) {
            abort(403, 'No puedes eliminar el usuario super administrador.');
        }

        $user->delete();

        return redirect()
            ->route('users.index', $restaurante)
            ->with('success', 'Usuario eliminado.');
    }
}
