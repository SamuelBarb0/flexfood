@php($restaurante = $restaurante ?? request()->route('restaurante'))

<div
    x-show="openCreate"
    x-transition
    class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50"
    style="display: none;"
>
    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6" @click.away="openCreate = false">
        <h2 class="text-xl font-bold text-[#153958] mb-4">Crear Usuario</h2>

        <form method="POST" action="{{ route('users.store', $restaurante) }}" class="space-y-4">
            @csrf
            <input type="hidden" name="restaurante_id" value="{{ $restaurante->id ?? '' }}">

            <div>
                <label class="block text-sm text-gray-700">Nombre</label>
                <input type="text" name="name" class="w-full border border-gray-300 rounded px-3 py-2" required>
            </div>

            <div>
                <label class="block text-sm text-gray-700">Email</label>
                <input type="email" name="email" class="w-full border border-gray-300 rounded px-3 py-2" required>
            </div>

            <div>
                <label class="block text-sm text-gray-700">Contraseña</label>
                <input type="password" name="password" class="w-full border border-gray-300 rounded px-3 py-2" required>
            </div>

            <div>
                <label class="block text-sm text-gray-700">Rol</label>
                <select name="role" class="w-full border border-gray-300 rounded px-3 py-2" required>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                    @endforeach
                </select>
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
