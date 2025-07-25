@foreach ($categorias as $categoria)
    @foreach ($categoria->productos as $producto)
        <div
            x-show="editProductoId === {{ $producto->id }}"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center"
        >
            <div class="bg-white rounded-lg p-6 w-full max-w-md" @click.away="editProductoId = null">
                <h2 class="text-lg font-semibold mb-4">Editar Producto</h2>
                <form action="{{ route('productos.update', $producto->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Nombre</label>
                        <input type="text" name="nombre" value="{{ $producto->nombre }}" class="w-full border rounded px-3 py-2" required>
                    </div>

                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Descripción</label>
                        <textarea name="descripcion" class="w-full border rounded px-3 py-2">{{ $producto->descripcion }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Precio</label>
                        <input type="number" step="0.01" name="precio" value="{{ $producto->precio }}" class="w-full border rounded px-3 py-2" required>
                    </div>

                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700">Imagen (opcional)</label>
                        <input type="file" name="imagen" accept="image/*" class="w-full border rounded px-3 py-2">
                        @if ($producto->imagen)
                            <img src="{{ asset('storage/' . $producto->imagen) }}" alt="Imagen actual" class="mt-2 h-16">
                        @endif
                    </div>

                    <div class="mb-3 flex items-center gap-2">
                        <input type="checkbox" name="disponible" value="1" {{ $producto->disponible ? 'checked' : '' }}>
                        <label class="text-sm text-gray-700">Disponible</label>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Categoría</label>
                        <select name="categoria_id" class="w-full border rounded px-3 py-2" required>
                            @foreach ($categorias as $cat)
                                <option value="{{ $cat->id }}" {{ $producto->categoria_id == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex justify-end">
                        <button type="button" @click="editProductoId = null" class="mr-2 text-gray-600">Cancelar</button>
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endforeach
