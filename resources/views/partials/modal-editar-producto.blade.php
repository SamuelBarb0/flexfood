@php
    $soloFotos = $soloFotos
        ?? (method_exists($restaurante, 'planLimits')
                ? (bool)($restaurante->planLimits()['only_photos'] ?? false)
                : in_array($restaurante->plan ?? 'legacy', ['basic','advanced'], true));
@endphp

<div
    x-show="editProductoId !== null && productoEditado"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center"
    x-data
>
    <div class="bg-white rounded-lg p-6 w-full max-w-md" @click.away="editProductoId = null">
        <h2 class="text-lg font-semibold mb-4">Editar Producto</h2>

        @if($soloFotos)
            <div class="mb-3 text-xs text-blue-700 bg-blue-50 border border-blue-200 rounded px-3 py-2">
                Este plan permite <strong>solo productos con imagen</strong>. Si el producto tenía video, se eliminará al guardar.
            </div>
        @endif

        <form :action="routeEditBase.replace('__ID__', productoEditado.id)" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" value="PUT">

            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Nombre</label>
                <input type="text" name="nombre" x-model="productoEditado.nombre" class="w-full border rounded px-3 py-2" required>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Descripción</label>
                <textarea name="descripcion" x-model="productoEditado.descripcion" class="w-full border rounded px-3 py-2"></textarea>
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Precio</label>
                <input type="number" step="0.01" name="precio" x-model="productoEditado.precio" class="w-full border rounded px-3 py-2" required>
            </div>

            {{-- Imagen --}}
            <div class="mb-3" x-data="{ imagenPreview: productoEditado.imagen ? `/images/${productoEditado.imagen}` : null }">
                <label class="block text-sm font-medium text-gray-700">Imagen {{ $soloFotos ? '(obligatoria)' : '(opcional)' }}</label>

                <input
                    type="file"
                    name="imagen"
                    accept="image/*"
                    class="w-full border rounded px-3 py-2"
                    @change="imagenPreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : imagenPreview"
                    @if($soloFotos) required @endif
                >

                <div class="mt-2">
                    <template x-if="imagenPreview">
                        <img :src="imagenPreview" alt="Vista previa" class="h-16 rounded shadow">
                    </template>
                </div>
            </div>

            {{-- Video oculto si es plan solo-fotos --}}
            @unless($soloFotos)
            <div class="mb-3" x-data="{ videoPreview: productoEditado.video ? `/images/${productoEditado.video}` : null }">
                <label class="block text-sm font-medium text-gray-700">Video (opcional)</label>

                <input
                    type="file"
                    name="video"
                    accept="video/mp4,video/webm,video/avi,video/mov"
                    class="w-full border rounded px-3 py-2"
                    @change="videoPreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : videoPreview"
                >

                <div class="mt-2">
                    <template x-if="videoPreview">
                        <video controls class="w-full h-40 rounded shadow">
                            <source :src="videoPreview">
                            Tu navegador no soporta el video.
                        </video>
                    </template>
                </div>
            </div>
            @endunless

            <div class="mb-3 flex items-center gap-2">
                <input type="checkbox" name="disponible" value="1" :checked="productoEditado.disponible">
                <label class="text-sm text-gray-700">Disponible</label>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Categoría</label>
                <select name="categoria_id" class="w-full border rounded px-3 py-2" required
                        @change="seleccionarCategoria($event.target.value)">
                    @foreach ($categorias as $cat)
                        <option :selected="productoEditado.categoria_id == {{ $cat->id }}" value="{{ $cat->id }}">
                            {{ $cat->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Adiciones</label>
                <div class="grid grid-cols-2 gap-2">
                    <template x-for="adic in adicionesDisponibles" :key="adic.id">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" :value="adic.id" name="adiciones[]"
                                   :checked="productoEditado.adiciones.includes(adic.id)">
                            <span class="text-sm text-gray-700" x-text="adic.nombre + ' (€' + (parseFloat(adic.precio) || 0).toFixed(2) + ')'"></span>
                        </label>
                    </template>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="button" @click="editProductoId = null" class="mr-2 text-gray-600">Cancelar</button>
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Actualizar</button>
            </div>
        </form>
    </div>
</div>
