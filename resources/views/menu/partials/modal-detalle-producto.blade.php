<!-- Modal Detalle Producto (revisado) -->
<div
  class="fixed inset-0 z-[100] flex items-center justify-center"
  :class="modalProducto ? 'pointer-events-auto' : 'pointer-events-none'"  {{-- 👈 clave --}}
  x-cloak
  x-data
>
  <!-- Overlay -->
  <div
    x-show="modalProducto"
    x-transition.opacity.duration.150ms
    class="absolute inset-0 bg-black/50"
    @click.self="modalProducto = false"   {{-- 👈 cierra solo si clic en overlay --}}
  ></div>

  <!-- Panel -->
  <div
    x-show="modalProducto"
    x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-120"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    class="relative bg-white rounded-lg w-full max-w-md p-6"
  >
    <button type="button" @click="modalProducto = false"
      class="absolute top-2 right-3 text-gray-500 hover:text-red-500 text-xl">×</button>

    <template x-if="productoSeleccionado">
      <div>
        <!-- Mostrar imagen si existe -->
        <template x-if="productoSeleccionado.imagen">
          <img :src="productoSeleccionado.imagen" alt=""
               class="w-full h-48 object-contain mb-4 rounded">
        </template>

        <!-- Si no hay imagen pero sí video, mostrar primer frame del video -->
        <template x-if="!productoSeleccionado.imagen && productoSeleccionado.video">
          <video :src="productoSeleccionado.video"
                 class="w-full h-48 object-contain mb-4 rounded"
                 preload="metadata"
                 muted>
          </video>
        </template>

        <h2 class="text-xl font-bold text-[#153958]" x-text="productoSeleccionado.nombre"></h2>
        <p class="text-gray-600 text-sm my-2" x-text="productoSeleccionado.descripcion"></p>

        <p class="text-[#3CB28B] text-lg font-semibold mb-4">
          €<span x-text="calcularPrecioTotal().toFixed(2)"></span>
        </p>

        <template x-if="productoSeleccionado.adiciones_disponibles && productoSeleccionado.adiciones_disponibles.length">
          <div>
            <p class="text-sm font-semibold text-[#153958] mb-2">Adiciones disponibles:</p>
            <template x-for="adicion in productoSeleccionado.adiciones_disponibles" :key="adicion.id">
              <label class="flex items-center space-x-2 mb-1">
                <input type="checkbox"
                  @change="
                    if ($event.target.checked) {
                      productoSeleccionado.adiciones.push(adicion);
                    } else {
                      productoSeleccionado.adiciones = productoSeleccionado.adiciones.filter(a => a.id !== adicion.id);
                    }
                  "
                  :checked="productoSeleccionado.adiciones.some(a => a.id === adicion.id)"
                  class="accent-[#3CB28B]">
                <span class="text-sm text-gray-700"
                      x-text="`${adicion.nombre} (€${(parseFloat(adicion.precio) || 0).toFixed(2)})`"></span>
              </label>
            </template>
          </div>
        </template>

        <button type="button" @click="agregarConAdiciones()"
          class="mt-4 w-full bg-[#3CB28B] text-white py-2 rounded hover:bg-[#2e9e75]">
          Agregar al carrito
        </button>
      </div>
    </template>
  </div>
</div>
