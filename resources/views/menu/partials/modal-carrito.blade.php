<!-- Sidebar carrito -->
<div x-show="mostrarCarrito" x-transition
     class="fixed top-0 right-0 w-full sm:w-96 h-full bg-white shadow-lg z-50 p-6 overflow-y-auto"
     @click.away="mostrarCarrito = false">

  <div class="flex justify-between items-center mb-4">
    <h2 class="text-xl font-bold text-[#153958]">🛒 Tu pedido</h2>
    <button @click="mostrarCarrito = false" class="text-gray-600 hover:text-red-500 text-xl">✖</button>
  </div>

  <template x-if="carrito.length === 0">
    <p class="text-sm text-gray-500">Tu carrito está vacío.</p>
  </template>

  <template x-for="item in carrito" :key="item.id + JSON.stringify(item.adiciones)">
    <div class="border-b py-3 flex justify-between items-start">
      <div>
        <p class="text-sm font-semibold text-[#153958]">
          <span x-text="item.nombre"></span>
          <span class="mx-1">×</span>
          <span x-text="item.cantidad"></span>
          <span class="text-xs text-gray-500 ml-1">
            (€/c/u <span x-text="parseFloat(item.precio_base).toFixed(2)"></span>)
          </span>
        </p>

        <!-- Adiciones -->
        <template x-if="item.adiciones && item.adiciones.length">
          <ul class="text-xs text-gray-500 list-disc ml-4 mt-1">
            <template x-for="ad in item.adiciones" :key="ad.id">
              <li>
                <span x-text="`${ad.nombre} (€${parseFloat(ad.precio).toFixed(2)})`"></span>
              </li>
            </template>
          </ul>
        </template>

        <p class="text-xs text-gray-500 mt-2">
          Subtotal: €<span x-text="(
            (parseFloat(item.precio_base || 0) +
            (item.adiciones ? item.adiciones.reduce((sum, a) => sum + parseFloat(a.precio || 0), 0) : 0)
          ) * item.cantidad).toFixed(2)"></span>
        </p>
      </div>

      <div class="flex items-center gap-2 mt-1">
        <button @click="quitarDelCarrito(item.id, item.adiciones)"
                class="bg-[#153958] text-white rounded-full w-6 h-6 text-sm flex items-center justify-center">-</button>

        <span class="w-6 text-center font-semibold" x-text="item.cantidad"></span>

        <!-- ¡Ojo! ahora incrementa ESTA línea (respeta adiciones) -->
        <button @click="incrementarLinea(item)"
                class="bg-[#3CB28B] text-white rounded-full w-6 h-6 text-sm flex items-center justify-center">+</button>
      </div>
    </div>
  </template>


  <div class="mt-6 border-t pt-4">
    <p class="text-lg font-bold text-[#153958]">
      Total: €<span x-text="totalPrecio.toFixed(2)"></span>
    </p>
    <button class="mt-4 w-full bg-[#3CB28B] text-white py-2 rounded hover:bg-[#2e9e75]"
            @click="enviarPedido">Finalizar pedido</button>
  </div>
</div>
