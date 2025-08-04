<div
    x-show="openDelete === {{ $user->id }}"
    x-transition
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    style="display: none;"
>
    <div class="bg-white rounded shadow-lg w-full max-w-md p-6" @click.away="openDelete = null">
        <h2 class="text-xl font-bold mb-4">Eliminar Usuario</h2>
        <p>¿Estás seguro de que deseas eliminar a <strong>{{ $user->name }}</strong>?</p>

        <form method="POST" action="{{ route('users.destroy', $user) }}" class="mt-4">
            @csrf @method('DELETE')
            <div class="flex justify-end gap-2">
                <button type="button" @click="openDelete = null" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-danger">Eliminar</button>
            </div>
        </form>
    </div>
</div>
