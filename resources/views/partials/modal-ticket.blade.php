<script>
    window.RESTAURANTE_NOMBRE = @json($restauranteNombre ?? $restaurante->nombre ?? 'Restaurante');
</script>

<!-- Modal Ticket Bonito -->
<div x-show="mostrarTicket"
    class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center" x-cloak>
    <div class="bg-white rounded-xl shadow-xl max-w-6xl w-full p-6 relative max-h-[90vh] overflow-y-auto" @click.away="mostrarTicket = false">

        <!-- Título -->
        <div class="flex justify-between items-start mb-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Resumen y Cierre - Mesa <span x-text="ticketActual?.mesa ?? ''"></span>
                </h2>
                <!-- Indicador de mesas fusionadas -->
                <div x-show="ticketActual?.fusionada" class="mt-1">
                    <span class="inline-flex items-center gap-1 bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-sm font-medium">
                        🔗 Mesas Fusionadas: <span x-text="ticketActual?.mesas_info"></span>
                    </span>
                </div>
            </div>
            <button @click="mostrarTicket = false" class="text-gray-400 hover:text-red-500 text-xl font-bold">×</button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Panel de Selección (Izquierda) -->
            <div class="lg:col-span-2 bg-gray-50 p-4 rounded-lg border">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-sm font-semibold text-gray-700">Productos del Ticket</h3>
                    <div class="flex gap-2">
                        <button @click="seleccionarTodos"
                                class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded hover:bg-blue-200">
                            ✓ Todos
                        </button>
                        <button @click="deseleccionarTodos"
                                class="text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded hover:bg-gray-300">
                            ✗ Ninguno
                        </button>
                    </div>
                </div>

                <!-- Lista de productos con checkboxes -->
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <template x-for="(item, index) in (ticketActual?.productos ?? [])" :key="index">
                        <div class="bg-white p-3 rounded border"
                             :class="{
                                 'bg-green-50 border-green-300': estaProductoPagado(item),
                                 'border-blue-300 ring-2 ring-blue-200': productosSeleccionados.includes(index)
                             }">
                            <div class="flex items-start gap-3">
                                <!-- Checkbox -->
                                <input type="checkbox"
                                       :value="index"
                                       x-model="productosSeleccionados"
                                       :disabled="estaProductoPagado(item)"
                                       class="mt-1 w-4 h-4 text-blue-600 rounded"
                                       :class="{'opacity-50 cursor-not-allowed': estaProductoPagado(item)}">

                                <!-- Info del producto -->
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-medium text-sm"
                                               :class="{'line-through text-green-700': estaProductoPagado(item)}">
                                                <span x-text="item.cantidad"></span>x
                                                <span x-text="item.nombre"></span>
                                            </p>

                                            <!-- Adiciones -->
                                            <template x-if="item.adiciones && item.adiciones.length > 0">
                                                <ul class="ml-4 mt-1 text-xs text-gray-500 list-disc">
                                                    <template x-for="adic in item.adiciones" :key="adic.id">
                                                        <li>
                                                            <span x-text="adic.nombre"></span>
                                                            <span x-text="`(+€${parseFloat(adic.precio).toFixed(2)})`"></span>
                                                        </li>
                                                    </template>
                                                </ul>
                                            </template>

                                            <!-- Badge de pagado -->
                                            <template x-if="estaProductoPagado(item)">
                                                <span class="inline-block mt-1 bg-green-600 text-white text-xs px-2 py-0.5 rounded">
                                                    ✓ PAGADO
                                                </span>
                                            </template>
                                        </div>

                                        <!-- Precio -->
                                        <div class="text-right">
                                            <p class="font-semibold text-sm"
                                               :class="{'text-green-700': estaProductoPagado(item)}"
                                               x-text="'€' + ((parseFloat(item.precio_base ?? item.precio) + (item.adiciones?.reduce((sum, a) => sum + parseFloat(a.precio), 0) || 0)) * item.cantidad).toFixed(2)">
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Panel de acciones para selección -->
                <div x-show="productosSeleccionados.length > 0"
                     class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex justify-between items-center mb-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">
                                <span x-text="productosSeleccionados.length"></span> producto(s) seleccionado(s)
                            </p>
                            <p class="text-lg font-bold text-blue-700">
                                Total: €<span x-text="calcularTotalSeleccionado().toFixed(2)"></span>
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button @click="marcarSeleccionadosComoPagados"
                                class="flex-1 bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700 font-medium">
                            💰 Marcar como PAGADO
                        </button>
                        <button @click="eliminarProductosSeleccionados"
                                class="flex-1 bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700 font-medium">
                            🗑️ Eliminar del ticket
                        </button>
                    </div>
                </div>
            </div>

            <!-- Panel Derecho: Resumen y Acciones -->
            <div class="space-y-4">
                <!-- Resumen de Totales -->
                <div class="bg-white border rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Resumen de Cuenta</h3>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Original:</span>
                            <span class="font-semibold" x-text="'€' + (ticketActual?.total ?? 0).toFixed(2)"></span>
                        </div>

                        <div class="flex justify-between text-green-700">
                            <span>Ya Pagado:</span>
                            <span class="font-semibold" x-text="'€' + calcularTotalPagado().toFixed(2)"></span>
                        </div>

                        <hr class="my-2">

                        <div class="flex justify-between text-lg font-bold text-blue-700">
                            <span>Pendiente:</span>
                            <span x-text="'€' + calcularTotalPendiente().toFixed(2)"></span>
                        </div>
                    </div>
                </div>

                <!-- Acciones del Ticket -->
                <div class="bg-white border rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Acciones del Ticket</h3>

                    <label class="block text-xs mb-1">Enviar por Email</label>
                    <div class="flex items-center space-x-2 mb-4">
                        <input type="email" x-model="emailDestino"
                               class="w-full px-2 py-1 border rounded text-sm bg-white text-gray-700"
                               placeholder="cliente@email.com">
                        <button @click="enviarTicketEmail"
                                class="bg-blue-500 text-white px-2 py-1 rounded text-xs hover:bg-blue-600">
                            ✉️
                        </button>
                    </div>

                    <button @click="generarPDFTicket"
                            class="w-full bg-gray-800 text-white py-2 rounded text-sm hover:bg-gray-900 flex items-center justify-center gap-2 mb-2">
                        🧾 Descargar PDF
                    </button>

                    <button @click="mostrarTicket = false; mostrarModal = true"
                            class="w-full bg-gray-200 text-gray-700 py-2 rounded text-sm hover:bg-gray-300">
                        ← Volver al TPV
                    </button>
                </div>

                <!-- Botón de Cierre -->
                <button @click="cerrarMesa"
                        :disabled="calcularTotalPendiente() > 0"
                        class="w-full bg-green-600 text-white py-3 rounded-lg text-sm hover:bg-green-700 font-semibold disabled:bg-gray-300 disabled:cursor-not-allowed"
                        :class="{'opacity-50': calcularTotalPendiente() > 0}">
                    <template x-if="calcularTotalPendiente() > 0">
                        <span>⚠️ Quedan €<span x-text="calcularTotalPendiente().toFixed(2)"></span> por pagar</span>
                    </template>
                    <template x-if="calcularTotalPendiente() <= 0">
                        <span>✅ Finalizar y Cerrar Mesa</span>
                    </template>
                </button>
            </div>
        </div>
    </div>
</div>
