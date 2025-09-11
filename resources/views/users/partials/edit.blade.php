@php
    // Contexto esperado: $user (a editar), $roles, $restaurante, $maxPerfiles, $perfilesActuales

    $restaurante      = $restaurante ?? request()->route('restaurante');
    $maxPerfiles      = $maxPerfiles ?? null;
    $perfilesActuales = $perfilesActuales ?? 0;

    // El editor/visor actual
    $viewer          = auth()->user();
    $viewerIsAdmin   = $viewer?->hasRole('administrador');
    $viewerIsRestAdm = $viewer?->hasRole('restauranteadmin');
    // Ocultar “Administrador” si el editor es restauranteadmin y NO es admin global
    $hideAdminRole   = $viewerIsRestAdm && !$viewerIsAdmin;

    // ¿El usuario editado ya ocupa cupo de cocina/cajero?
    $userEsKC = $user->hasRole('cocina') || $user->hasRole('cajero');
@endphp

<div
    x-show="openEdit === {{ $user->id }}"
    x-transition
    class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50"
    style="display: none;"
>
    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6" @click.away="openEdit = null">
        <h2 class="text-xl font-bold text-[#153958] mb-4">Editar Usuario</h2>

        @if(!is_null($maxPerfiles))
            <div class="mb-3 p-2 rounded bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm">
                Perfiles (cocina + cajero): <strong>{{ $perfilesActuales }} / {{ $maxPerfiles }}</strong>
            </div>
        @endif

        <form method="POST" action="{{ route('users.update', [$restaurante, $user]) }}" class="space-y-4">
            @csrf @method('PUT')
            <input type="hidden" name="restaurante_id" value="{{ $restaurante->id ?? '' }}">

            <div>
                <label class="block text-sm text-gray-700">Nombre</label>
                <input type="text" name="name" value="{{ $user->name }}"
                       class="w-full border border-gray-300 rounded px-3 py-2" required>
            </div>

            <div>
                <label class="block text-sm text-gray-700">Email</label>
                <input type="email" name="email" value="{{ $user->email }}"
                       class="w-full border border-gray-300 rounded px-3 py-2" required>
            </div>

            <div>
                <label class="block text-sm text-gray-700">Nueva Contraseña (opcional)</label>
                <input type="password" name="password" class="w-full border border-gray-300 rounded px-3 py-2">
            </div>

            <div>
                <label class="block text-sm text-gray-700">Rol</label>
                <select name="role" class="w-full border border-gray-300 rounded px-3 py-2" required>
                    @foreach($roles as $role)
                        @php
                            $roleName  = strtolower($role->name);
                            $isKC      = in_array($roleName, ['cocina','cajero']);
                            // Deshabilitar cambio a KC si el plan está lleno y este usuario NO era KC
                            $disableKC = !is_null($maxPerfiles)
                                         && $perfilesActuales >= $maxPerfiles
                                         && $isKC
                                         && !$userEsKC;
                        @endphp

                        {{-- Ocultar “Administrador” si corresponde --}}
                        @if($hideAdminRole && $roleName === 'administrador')
                            @continue
                        @endif

                        <option value="{{ $role->name }}"
                                @selected($user->hasRole($role->name))
                                @disabled($disableKC)>
                            {{ ucfirst($role->name) }}
                            @if($isKC && !is_null($maxPerfiles))
                                ({{ $perfilesActuales }}/{{ $maxPerfiles }})
                            @endif
                            @if($disableKC) — límite alcanzado @endif
                        </option>
                    @endforeach
                </select>

                @if($hideAdminRole)
                    <p class="mt-1 text-xs text-gray-500">
                        Como <strong>Administrador del restaurante</strong>, no puedes asignar el rol <em>Administrador</em>.
                    </p>
                @endif

                @if(!is_null($maxPerfiles) && $perfilesActuales >= $maxPerfiles && !$userEsKC)
                    <p class="mt-1 text-xs text-red-600">
                        No puedes asignar cocina/cajero: el límite de tu plan ya fue alcanzado.
                    </p>
                @elseif($userEsKC && !is_null($maxPerfiles) && $perfilesActuales >= $maxPerfiles)
                    <p class="mt-1 text-xs text-yellow-700">
                        Este usuario ya ocupa un cupo de cocina/cajero. Puedes mantener su rol aunque el límite esté lleno.
                    </p>
                @endif
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <button type="button" @click="openEdit = null"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded">
                    Cancelar
                </button>
                <button type="submit"
                        class="bg-[#153958] hover:bg-[#122d48] text-white px-4 py-2 rounded">
                    Actualizar
                </button>
            </div>
        </form>
    </div>
</div>
