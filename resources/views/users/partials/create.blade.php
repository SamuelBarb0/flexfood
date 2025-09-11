@php
    $restaurante      = $restaurante ?? request()->route('restaurante');
    $maxPerfiles      = $maxPerfiles ?? null;
    $perfilesActuales = $perfilesActuales ?? 0;

    // Rol del usuario autenticado
    $currentUserRole = strtolower(auth()->user()->getRoleNames()->first() ?? '');

    // Filtrar roles visibles en "Crear":
    // - Si es restauranteadmin, ocultar "administrador"/"administrador global"
    $rolesToShow = collect($roles)->filter(function ($role) use ($currentUserRole) {
        $r = strtolower($role->name);
        if ($currentUserRole === 'restauranteadmin' && in_array($r, ['administrador','admin','administrador global'])) {
            return false;
        }
        return true;
    })->values();

    // Rol por defecto = el primero permitido (o el que venga en old('role'))
    $defaultRole = old('role');
    if (!$defaultRole && $rolesToShow->isNotEmpty()) {
        $defaultRole = $rolesToShow->first()->name;
    }
@endphp

<div
    x-show="openCreate"
    x-transition
    class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50"
    style="display: none;"
>
    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6" @click.away="openCreate = false">
        <h2 class="text-xl font-bold text-[#153958] mb-4">Crear Usuario</h2>

        @if(!is_null($maxPerfiles))
            <div class="mb-3 p-2 rounded bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm">
                Perfiles (cocina + cajero): <strong>{{ $perfilesActuales }} / {{ $maxPerfiles }}</strong>
            </div>
        @endif

        <form method="POST" action="{{ route('users.store', $restaurante) }}" class="space-y-4">
            @csrf
            <input type="hidden" name="restaurante_id" value="{{ $restaurante->id ?? '' }}">

            <div>
                <label class="block text-sm text-gray-700">Nombre</label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full border border-gray-300 rounded px-3 py-2" required>
            </div>

            <div>
                <label class="block text-sm text-gray-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="w-full border border-gray-300 rounded px-3 py-2" required>
            </div>

            <div>
                <label class="block text-sm text-gray-700">Contraseña</label>
                <input type="password" name="password" class="w-full border border-gray-300 rounded px-3 py-2" required>
            </div>

            <div>
                <label class="block text-sm text-gray-700">Rol</label>

                @if($rolesToShow->isEmpty())
                    <div class="text-sm text-red-600">
                        No hay roles disponibles para asignar con tu nivel de acceso.
                    </div>
                @else
                    <select name="role" class="w-full border border-gray-300 rounded px-3 py-2" required>
                        @foreach($rolesToShow as $role)
                            @php
                                $r         = strtolower($role->name);
                                $isKC      = in_array($r, ['cocina','cajero']);
                                // Bloquear cocina/cajero si alcanzaste el tope del plan
                                $disableKC = !is_null($maxPerfiles) && $perfilesActuales >= $maxPerfiles && $isKC;
                            @endphp
                            <option value="{{ $role->name }}"
                                    @selected(old('role', $defaultRole) === $role->name)
                                    @disabled($disableKC)>
                                {{ ucfirst($role->name) }}
                                @if($isKC && !is_null($maxPerfiles))
                                    ({{ $perfilesActuales }}/{{ $maxPerfiles }})
                                @endif
                                @if($disableKC) — límite alcanzado @endif
                            </option>
                        @endforeach
                    </select>

                    @if(!is_null($maxPerfiles) && $perfilesActuales >= $maxPerfiles)
                        <p class="mt-1 text-xs text-red-600">
                            Límite de perfiles de cocina/cajero alcanzado para tu plan.
                        </p>
                    @endif
                @endif
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <button type="button" @click="openCreate = false"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded">
                    Cancelar
                </button>
                <button type="submit"
                        class="bg-[#3CB28B] hover:bg-[#319c78] text-white px-4 py-2 rounded">
                    Crear
                </button>
            </div>
        </form>
    </div>
</div>
