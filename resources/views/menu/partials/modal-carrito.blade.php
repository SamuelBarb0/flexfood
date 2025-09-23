<!-- Sidebar carrito -->
<div x-show="mostrarCarrito" x-transition
  class="fixed top-0 right-0 w-full sm:w-96 h-full bg-white shadow-lg z-[100] p-6 overflow-y-auto"
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

        <!-- incrementa ESTA línea (respeta adiciones) -->
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

  <!-- ===== Pedidos ENTREGADOS (estado 2) debajo de Finalizar pedido ===== -->
  <div class="mt-8">
    <div class="flex items-center justify-between mb-2">
      <h3 class="text-base font-bold text-[#153958]">✅ Pedidos entregados</h3>
      <button
        type="button"
        class="text-xs text-[#153958] underline"
        @click="cargarEntregados()"
        x-show="!!mesa_id">
        Actualizar
      </button>
    </div>

    <!-- Cargando -->
    <p class="text-xs text-gray-500" x-show="cargandoEntregados">
      Cargando pedidos…
    </p>

<!-- Lista de pedidos entregados (estado 2) -->
<template x-if="pedidosEntregados.length > 0">
  <div class="space-y-3">
    <!-- ENVOLVEMOS TODO EN UN SOLO CONTENEDOR POR CADA 'ped' -->
    <template x-for="ped in pedidosEntregados" :key="ped.id">
      <div class="space-y-3">
        <!-- Card del pedido -->
        <div class="border rounded-lg p-3">
          <div class="flex items-center justify-between">
            <p class="text-sm font-semibold text-[#153958]">
              Pedido #<span x-text="ped.id"></span> ·
              <span class="text-green-600" x-show="Number(ped.estado) === 2">Entregado</span>
              <span class="text-amber-600" x-show="Number(ped.estado) === 3">Cuenta solicitada</span>
            </p>
            <p class="text-xs text-gray-500" x-text="(new Date(ped.created_at || ped.fecha || Date.now())).toLocaleString()"></p>
          </div>

          <!-- Items del pedido -->
          <div class="mt-2 space-y-1">
            <template x-for="it in (ped.items || ped.detalles || [])" :key="(it.id ?? it.nombre) + '-' + (JSON.stringify(it.adiciones || []))">
              <div class="text-sm">
                <span class="font-medium text-[#153958]" x-text="it.nombre"></span>
                <span class="mx-1">×</span>
                <span x-text="it.cantidad || 1"></span>
                <span class="text-xs text-gray-500 ml-1">
                  (€/c/u <span x-text="parseFloat(it.precio_base ?? it.precio ?? 0).toFixed(2)"></span>)
                </span>

                <!-- Adiciones -->
                <template x-if="it.adiciones && it.adiciones.length">
                  <ul class="text-xs text-gray-500 list-disc ml-4 mt-1">
                    <template x-for="ad in it.adiciones" :key="ad.id || ad.nombre">
                      <li>
                        <span x-text="`${ad.nombre} (€${parseFloat(ad.precio || 0).toFixed(2)})`"></span>
                      </li>
                    </template>
                  </ul>
                </template>
              </div>
            </template>
          </div>

          <!-- Total del pedido entregado -->
          <div class="mt-2 flex items-center justify-between">
            <span class="text-sm text-gray-600">Total pedido</span>
            <span class="text-sm font-bold text-[#153958]"
                  x-text="(ped.total != null ? parseFloat(ped.total) : (
                            (ped.items || ped.detalles || []).reduce((acc, it) => {
                              const pb = parseFloat(it.precio_base ?? it.precio ?? 0);
                              const ads = (it.adiciones || []).reduce((s, a) => s + parseFloat(a.precio || 0), 0);
                              const qty = parseInt(it.cantidad || 1);
                              return acc + (pb + ads) * qty;
                            }, 0)
                         )).toFixed(2)">
            </span>
          </div>
        </div>

        <!-- Botón individual (mismo scope de 'ped') -->
        <div class="mt-1">
          <button
  class="w-full bg-[#153958] text-white py-2 rounded hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed"
  :disabled="!puedePedirCuenta(ped)"
  @click="pedirCuentaPedido(ped.id)"
  x-text="Number(ped.estado) === 2 ? 'Pedir la cuenta' : 'Cuenta solicitada…'">
</button>

          <p class="text-[11px] text-gray-500 mt-1">
            Al solicitar la cuenta de este pedido, los demás quedan bloqueados hasta que se cierre (estado 4).
          </p>
        </div>
      </div>
    </template>
  </div>
</template>

    <!-- No hay pedidos entregados -->
    <p class="text-xs text-gray-500" x-show="!cargandoEntregados && mesa_id && pedidosEntregados.length === 0">
      Aún no hay pedidos entregados para esta mesa.
    </p>
  </div>
</div>