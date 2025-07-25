@foreach ($categorias as $categoria)
    <div
        x-show="editCategoriaId === {{ $categoria->id }}"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center"
    >
        <div class="bg-white rounded-lg p-6 w-full max-w-md" @click.away="editCategoriaId = null">
            <h2 class="text-lg font-semibold mb-4">Editar Categoría</h2>
            <form action="{{ route('categorias.update', $categoria->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Nombre</label>
                    <input type="text" name="nombre" value="{{ $categoria->nombre }}" class="mt-1 block w-full border rounded px-3 py-2" required>
                </div>
                <div class="flex justify-end">
                    <button type="button" @click="editCategoriaId = null" class="mr-2 text-gray-600">Cancelar</button>
                    <button type="submit" class="bg-[#153958] text-white px-4 py-2 rounded">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
