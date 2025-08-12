@php($restaurante = $restaurante ?? request()->route('restaurante'))

<div
    x-show="openDelete === {{ $user->id }}"
    x-transition
    class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50"
    style="display: none;"
>
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 text-center" @click.away="openDelete = null">
        <h2 class="text-xl font-bold text-[#e53e3e] mb-4">¿Eliminar Usuario?</h2>
        <p class="mb-6 text-gray-700">Estás a punto de eliminar a <strong>{{ $user->name }}</strong>.</p>

        <form method="POST" action="{{ route('users.destroy', [$restaurante, $user]) }}">
            @csrf @method('DELETE')
            <div class="flex justify-center gap-3">
                <button type="button" @click="openDelete = null"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded">
                    Cancelar
                </button>
                <button type="submit"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                    Eliminar
                </button>
            </div>
        </form>
    </div>
</div>
