@php
    // Usa el valor pasado desde el controlador si existe; si no, calcula desde el restaurante
    $soloFotos = $soloFotos
        ?? (method_exists($restaurante, 'planLimits')
                ? (bool)($restaurante->planLimits()['only_photos'] ?? false)
                : in_array($restaurante->plan ?? 'legacy', ['basic','advanced'], true));
@endphp

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

        @if($soloFotos)
            <div class="mb-3 text-xs text-blue-700 bg-blue-50 border border-blue-200 rounded px-3 py-2">
                Este plan permite <strong>solo productos con imagen</strong> (sin video).
            </div>
        @endif

        <form action="{{ route('productos.store', $restaurante) }}" method="POST" enctype="multipart/form-data">
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
                <input
                    type="file"
                    name="imagen"
                    accept="image/*"
                    class="w-full border rounded px-3 py-2"
                    @if($soloFotos) required @endif
                >
            </div>

            {{-- Video oculto si es plan solo-fotos --}}
            @unless($soloFotos)
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Video (opcional)</label>
                <input type="file" name="video" accept="video/mp4,video/webm,video/avi,video/mov" class="w-full border rounded px-3 py-2">
            </div>
            @endunless

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
                            <span class="text-sm text-gray-700" x-text="adic.nombre + ' (€' + (parseFloat(adic.precio) || 0).toFixed(2) + ')'"></span>
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
