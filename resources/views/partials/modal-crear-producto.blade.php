<div
    x-show="openProducto"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center"
    x-data="{
        categoriaSeleccionada: null,
        adicionesDisponibles: [],
        seleccionarCategoria(id) {
            this.categoriaSeleccionada = id;
            this.adicionesDisponibles = window.adicionesPorCategoria[id] || [];
        }
    }"
>
    <div class="bg-white rounded-lg p-6 w-full max-w-md" @click.away="openProducto = false">
        <h2 class="text-lg font-semibold mb-4">Crear Producto</h2>
        <form action="{{ route('productos.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Nombre</label>
                <input type="text" name="nombre" class="w-full border rounded px-3 py-2" required>
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Descripción</label>
                <textarea name="descripcion" class="w-full border rounded px-3 py-2"></textarea>
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Precio</label>
                <input type="number" step="0.01" name="precio" class="w-full border rounded px-3 py-2" required>
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Imagen</label>
                <input type="file" name="imagen" accept="image/*" class="w-full border rounded px-3 py-2">
            </div>
            <div class="mb-3 flex items-center gap-2">
                <input type="checkbox" name="disponible" value="1" checked>
                <label class="text-sm text-gray-700">Disponible</label>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Categoría</label>
                <select name="categoria_id" class="w-full border rounded px-3 py-2" required
                        @change="seleccionarCategoria($event.target.value)">
                    <option value="">Seleccione una categoría</option>
                    @foreach ($categorias as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Adiciones dinámicas --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Adiciones</label>
                <div class="grid grid-cols-2 gap-2">
                    <template x-for="adic in adicionesDisponibles" :key="adic.id">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" :value="adic.id" name="adiciones[]">
                            <span class="text-sm text-gray-700" x-text="adic.nombre + ' (€' + parseFloat(adic.precio).toFixed(2) + ')'"></span>
                        </label>
                    </template>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="button" @click="openProducto = false" class="mr-2 text-gray-600">Cancelar</button>
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Guardar</button>
            </div>
        </form>
    </div>
</div>
