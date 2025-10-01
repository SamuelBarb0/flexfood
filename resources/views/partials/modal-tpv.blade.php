<!-- Modal TPV -->
<div
    x-show="mostrarModal"
    class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center"
    x-cloak>
    <div class="bg-white rounded-lg w-full max-w-4xl p-6 relative" @click.self="mostrarModal = false">
        <button @click="mostrarModal = false" class="absolute top-3 right-4 text-gray-500 text-xl">×</button>

                <!-- 🔧 aquí el fix -->
        <div class="mb-4">
          <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold">
              TPV - Mesa <span x-text="mesaSeleccionada?.numero ?? ''"></span>
              <span class="text-xs text-gray-500" x-text="'(Estado: ' + estadoMesa + ')'"></span>
            </h2>
            <button
              @click="refrescarCuentaActual()"
              class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm flex items-center space-x-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
              <span>Actualizar</span>
            </button>
          </div>

          <!-- Alerta de mesa fusionada -->
          <template x-if="mesaEstaFusionada()">
            <div class="mt-2 bg-purple-50 border border-purple-200 rounded-lg p-3 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="text-purple-600 text-lg">🔗</span>
                <div class="text-sm">
                  <p class="font-semibold text-purple-800">Mesa fusionada</p>
                  <p class="text-purple-600 text-xs" x-text="'Grupo: ' + getMesasGrupoInfo()"></p>
                </div>
              </div>
              <button
                @click="desfusionarMesa(mesaSeleccionada?.id)"
                class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded text-xs font-medium">
                Desfusionar
              </button>
            </div>
          </template>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Cuenta Actual -->
            <div class="bg-gray-50 p-4 rounded border flex flex-col">
                <h3 class="font-semibold text-gray-700 mb-2">
                    Cuenta Actual
                    <template x-if="mesaEstaFusionada()">
                        <span class="text-xs text-purple-600 font-normal ml-2">(Mesas fusionadas)</span>
                    </template>
                </h3>

                <!-- Contenedor con scroll para los productos -->
                <div class="flex-1 max-h-80 overflow-y-auto border rounded-md bg-white p-2"
                     style="scrollbar-width: thin; scrollbar-color: #CBD5E0 #F7FAFC;">
                    <template x-if="cuentaActual.length === 0">
                        <div class="text-gray-400 text-sm italic">No hay productos aún.</div>
                    </template>

                    <!-- Agrupar por mesa si hay fusión -->
                    <template x-for="grupo in productosPorMesa" :key="grupo.mesa">
                        <div class="mb-4">
                            <!-- Encabezado de mesa (solo si hay fusión) -->
                            <template x-if="grupo.mesa">
                                <div class="bg-purple-600 text-white px-3 py-2 rounded-lg text-sm font-bold mb-3 sticky top-0 shadow-md flex items-center gap-2">
                                    <span class="text-base">📍</span>
                                    <span>Mesa <span x-text="grupo.mesa"></span></span>
                                </div>
                            </template>

                            <!-- Productos de esta mesa -->
                            <template x-for="(item, index) in grupo.productos" :key="item.nombre + JSON.stringify(item.adiciones) + index">
                                <div class="mb-2 text-sm text-gray-800 border rounded p-2"
                             :class="getEstadoEntregaClasses(item)">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center space-x-2 flex-1">
                                    <!-- Indicador visual de entrega -->
                                    <div class="flex-shrink-0" x-show="estadoMesa !== 'Libre'">
                                        <template x-if="getEstadoEntrega(item) === 'completo'">
                                            <span class="w-4 h-4 bg-green-500 rounded-full flex items-center justify-center">
                                                <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        </template>
                                        <template x-if="getEstadoEntrega(item) === 'parcial'">
                                            <span class="w-4 h-4 bg-orange-500 rounded-full flex items-center justify-center text-white text-xs">
                                                📦
                                            </span>
                                        </template>
                                        <template x-if="getEstadoEntrega(item) === 'pendiente'">
                                            <span class="w-4 h-4 bg-gray-400 rounded-full flex items-center justify-center text-white text-xs">
                                                ⏳
                                            </span>
                                        </template>
                                    </div>

                                    <div class="flex-1">
                                        <span x-text="`${item.cantidad}x ${item.nombre}`"></span>

                                        <!-- Estado de entrega para mesas no libres -->
                                        <template x-if="estadoMesa !== 'Libre'">
                                            <div class="text-xs" :class="getEstadoEntregaTextClass(item)">
                                                <template x-if="getEstadoEntrega(item) === 'completo'">
                                                    <span>✅ Completamente entregado</span>
                                                </template>
                                                <template x-if="getEstadoEntrega(item) === 'parcial'">
                                                    <span x-text="`📦 ${getCantidadEntregada(item)}/${item.cantidad} entregado`"></span>
                                                </template>
                                                <template x-if="getEstadoEntrega(item) === 'pendiente'">
                                                    <span>⏳ Pendiente de entrega</span>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <span x-text="`${((parseFloat(item.precio_base ?? item.precio) + (item.adiciones?.reduce((sum, a) => sum + parseFloat(a.precio), 0) || 0)) * item.cantidad).toFixed(2)} €`"></span>
                                    <button
                                        @click="item.cantidad > 1 ? item.cantidad-- : cuentaActual.splice(index, 1)"
                                        class="text-red-500 hover:text-red-700 ml-2 text-sm"
                                        title="Quitar uno"
                                        x-show="estadoMesa === 'Libre'">🗑️</button>
                                </div>
                            </div>

                            <!-- Mostrar precio base -->
                            <div class="ml-6 text-xs text-gray-500" x-show="estadoMesa === 'Libre'">
                                Precio base: €<span x-text="parseFloat(item.precio_base ?? item.precio).toFixed(2)"></span>
                            </div>

                            <!-- Mostrar adiciones si existen -->
                            <template x-if="item.adiciones && item.adiciones.length > 0">
                                <ul class="ml-6 mt-1 text-xs text-gray-500 list-disc">
                                    <template x-for="adic in item.adiciones" :key="adic.id">
                                        <li>
                                            <span x-text="adic.nombre"></span>
                                            <span x-text="`(+€${(parseFloat(adic.precio) || 0).toFixed(2)})`"></span>
                                        </li>
                                    </template>
                                </ul>
                            </template>
                        </div>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Sección fija inferior (fuera del scroll) -->
                <div class="mt-3 pt-3 border-t">
                    <!-- Resumen de estado de entrega para mesas no libres -->
                    <template x-if="estadoMesa !== 'Libre' && cuentaActual.length > 0">
                        <div class="mb-3 p-2 bg-blue-50 rounded border border-blue-200">
                            <h4 class="text-xs font-semibold text-blue-800 mb-1">Estado de Entrega:</h4>
                            <div class="grid grid-cols-3 gap-2 text-xs">
                                <div class="text-center">
                                    <div class="text-green-600 font-medium" x-text="getProductosCompletos()"></div>
                                    <div class="text-gray-500">Entregados</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-orange-600 font-medium" x-text="getProductosParciales()"></div>
                                    <div class="text-gray-500">Parciales</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-gray-500 font-medium" x-text="getProductosPendientes()"></div>
                                    <div class="text-gray-500">Pendientes</div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div class="text-right font-bold text-lg">
                        Total: <span x-text="totalCuenta.toFixed(2) + ' €'"></span>
                    </div>
                </div>
            </div>

            <!-- Añadir a la Cuenta -->
            <div class="bg-white p-4 rounded border">
                <h3 class="font-semibold text-gray-700 mb-2">Añadir a la Cuenta</h3>
                <input
                    type="text"
                    placeholder="Buscar por nombre..."
                    class="w-full px-3 py-2 mb-3 border rounded text-sm"
                    x-model="busqueda">

                <div class="space-y-4 max-h-64 overflow-y-auto">
                    <template x-for="categoria in categoriasFiltradas" :key="categoria.id">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-600 mb-1" x-text="categoria.nombre"></h4>

                            <template
                                x-for="producto in categoria.productos.filter(p =>
                                    p.disponible == 1 &&
                                    p.nombre.toLowerCase().includes(busqueda.toLowerCase())
                                )"
                                :key="producto.id">
                                <div class="flex justify-between items-center hover:bg-gray-100 p-2 cursor-pointer rounded"
                                    @click="abrirDetalleProducto(producto)">
                                    <div class="flex-1">
                                        <span x-text="producto.nombre" class="text-sm font-medium"></span>
                                        <div x-text="producto.descripcion || ''" class="text-xs text-gray-500 truncate"></div>
                                    </div>
                                    <div class="text-right">
                                        <span x-text="parseFloat(producto.precio).toFixed(2) + '€'" class="text-sm font-medium"></span>
                                        <div class="text-xs text-gray-400">
                                            <span x-show="producto.adiciones && producto.adiciones.length > 0" x-text="'+ ' + (producto.adiciones?.length || 0) + ' adiciones'"></span>
                                            <span x-show="!producto.adiciones || producto.adiciones.length === 0" class="text-gray-300">Sin adiciones</span>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template x-if="categoria.productos.filter(p =>
                                p.disponible == 1 &&
                                p.nombre.toLowerCase().includes(busqueda.toLowerCase())
                            ).length === 0">
                                <div class="text-xs text-gray-400 italic">No hay resultados.</div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-between items-center">
            <div>
                <button @click="mostrarModal = false" class="bg-gray-200 px-4 py-2 rounded text-gray-700 text-sm">Cancelar</button>
            </div>
            <div class="flex space-x-2">
                <template x-if="estadoMesa === 'Libre'">
                    <button @click="enviarPedido()"
                            :disabled="cuentaActual.length === 0"
                            :class="cuentaActual.length === 0 ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700'"
                            class="text-white px-4 py-2 rounded text-sm">
                        Enviar Pedido
                    </button>
                </template>
                <template x-if="estadoMesa !== 'Libre'">
                    <button @click="mostrarModalTraspasar = true"
                            class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded text-sm">
                        🔄 Traspasar Mesa
                    </button>
                </template>
                <template x-if="estadoMesa !== 'Libre'">
                    <button @click="gestionarTicket" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                        Gestionar Ticket
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- Modal Detalle Producto -->
    <div x-show="mostrarDetalleProducto"
         class="fixed inset-0 bg-black/60 z-60 flex items-center justify-center"
         x-cloak
         x-transition>
        <div class="bg-white rounded-lg w-full max-w-md p-6 mx-4" @click.away="cerrarDetalleProducto()">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold" x-text="productoSeleccionado?.nombre || ''"></h3>
                <button @click="cerrarDetalleProducto()" class="text-gray-500 text-xl">&times;</button>
            </div>

            <template x-if="productoSeleccionado">
                <div>
                    <!-- Descripción del producto -->
                    <div x-show="productoSeleccionado.descripcion" class="mb-4">
                        <p class="text-sm text-gray-600" x-text="productoSeleccionado.descripcion"></p>
                    </div>

                    <!-- Precio base -->
                    <div class="mb-4">
                        <span class="text-lg font-semibold text-gray-800">
                            €<span x-text="parseFloat(productoSeleccionado.precio).toFixed(2)"></span>
                        </span>
                    </div>

                    <!-- Adiciones disponibles -->
                    <template x-if="productoSeleccionado.adiciones && productoSeleccionado.adiciones.length > 0">
                        <div class="mb-4">
                            <h4 class="font-medium mb-2">Adiciones disponibles:</h4>
                            <div class="space-y-2 max-h-32 overflow-y-auto">
                                <template x-for="adicion in productoSeleccionado.adiciones" :key="adicion.id">
                                    <label class="flex items-center space-x-2 text-sm cursor-pointer">
                                        <input type="checkbox"
                                               :value="adicion.id"
                                               @change="toggleAdicion(adicion)"
                                               class="rounded">
                                        <span x-text="adicion.nombre"></span>
                                        <span class="text-gray-500">
                                            (+€<span x-text="(parseFloat(adicion.precio) || 0).toFixed(2)"></span>)
                                        </span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Precio total calculado -->
                    <div class="mb-4 p-3 bg-gray-50 rounded">
                        <div class="flex justify-between items-center">
                            <span class="font-medium">Total:</span>
                            <span class="text-lg font-semibold text-green-600">
                                €<span x-text="calcularPrecioConAdiciones().toFixed(2)"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex space-x-2">
                        <button @click="cerrarDetalleProducto()"
                                class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm">
                            Cancelar
                        </button>
                        <button @click="agregarProductoConAdiciones()"
                                class="flex-1 bg-blue-600 text-white px-4 py-2 rounded text-sm">
                            Añadir al pedido
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Modal Traspasar Mesa -->
    <div x-show="mostrarModalTraspasar"
         class="fixed inset-0 bg-black/60 z-60 flex items-center justify-center"
         x-cloak
         x-transition>
        <div class="bg-white rounded-lg w-full max-w-lg p-6 mx-4" @click.away="mostrarModalTraspasar = false">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">🔄 Traspasar Mesa</h3>
                <button @click="mostrarModalTraspasar = false" class="text-gray-500 text-xl">&times;</button>
            </div>

            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-2">
                    Selecciona la mesa destino para traspasar el ticket de <strong>Mesa <span x-text="mesaSeleccionada?.numero"></span></strong>
                </p>
                <div class="bg-yellow-50 border border-yellow-200 rounded p-3 text-xs text-yellow-800">
                    ℹ️ Si la mesa destino está ocupada, los productos se fusionarán automáticamente.
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Mesa Destino:</label>
                <select x-model="mesaDestinoId" class="w-full px-3 py-2 border rounded text-sm bg-white">
                    <option value="">-- Selecciona una mesa --</option>
                    <template x-for="mesa in mesas.filter(m => m.id !== mesaSeleccionada?.id)" :key="mesa.id">
                        <option :value="mesa.id">
                            <span x-text="'Mesa ' + mesa.numero + ' (' + mesa.estado_texto + ')'"></span>
                        </option>
                    </template>
                </select>
            </div>

            <div class="flex space-x-2">
                <button @click="mostrarModalTraspasar = false"
                        class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm">
                    Cancelar
                </button>
                <button @click="confirmarTraspaso()"
                        :disabled="!mesaDestinoId"
                        :class="!mesaDestinoId ? 'bg-gray-400 cursor-not-allowed' : 'bg-orange-600 hover:bg-orange-700'"
                        class="flex-1 text-white px-4 py-2 rounded text-sm">
                    Confirmar Traspaso
                </button>
            </div>
        </div>
    </div>
</div>