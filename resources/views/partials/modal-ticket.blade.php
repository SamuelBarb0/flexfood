<script>
    window.RESTAURANTE_NOMBRE = @json($restauranteNombre ?? $restaurante->nombre ?? 'Restaurante');
</script>

<!-- Modal Ticket Bonito -->
<div x-show="mostrarTicket"
    class="fixed inset-0 bg-black/50 z-[70] flex items-end sm:items-center justify-center p-0 sm:p-4" x-cloak>
    <div class="bg-white rounded-t-2xl sm:rounded-xl shadow-xl max-w-6xl w-full p-4 sm:p-6 relative max-h-[95vh] overflow-y-auto" @click.away="mostrarTicket = false">

        <!-- Título -->
        <div class="flex flex-col sm:flex-row justify-between items-start gap-3 mb-4 sticky top-0 bg-white pb-3 border-b sm:border-0 z-10">
            <div class="flex-1">
                <h2 class="text-lg sm:text-xl font-semibold text-gray-800">
                    Resumen y Cierre - Mesa <span x-text="ticketActual?.mesa ?? ''"></span>
                </h2>
                <!-- Indicador de mesas fusionadas -->
                <div x-show="ticketActual?.fusionada" class="mt-1">
                    <span class="inline-flex items-center gap-1 bg-purple-100 text-purple-700 px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm font-medium">
                        🔗 Fusionadas: <span x-text="ticketActual?.mesas_info"></span>
                    </span>
                </div>
            </div>
            <button @click="mostrarTicket = false" class="text-gray-400 hover:text-red-500 text-2xl sm:text-xl font-bold absolute top-2 right-2 sm:relative sm:top-0 sm:right-0">×</button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
            <!-- Panel de Selección (Izquierda) -->
            <div class="lg:col-span-2 bg-gray-50 p-3 sm:p-4 rounded-lg border order-2 lg:order-1">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-3 gap-2">
                    <h3 class="text-sm font-semibold text-gray-700">Productos del Ticket</h3>
                    <div class="flex gap-2 w-full sm:w-auto">
                        <button @click="seleccionarTodos"
                                class="text-xs bg-blue-100 text-blue-700 px-3 py-1.5 rounded hover:bg-blue-200 flex-1 sm:flex-none">
                            ✓ Todos
                        </button>
                        <button @click="deseleccionarTodos"
                                class="text-xs bg-gray-200 text-gray-700 px-3 py-1.5 rounded hover:bg-gray-300 flex-1 sm:flex-none">
                            ✗ Ninguno
                        </button>
                    </div>
                </div>

                <!-- Lista de productos con checkboxes -->
                <div class="space-y-2 max-h-64 sm:max-h-96 overflow-y-auto">
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
                                                            <span x-text="`(+€${(parseFloat(adic.precio) || 0).toFixed(2)})`"></span>
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
                     class="mt-4 p-3 sm:p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-3 gap-2">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">
                                <span x-text="productosSeleccionados.length"></span> producto(s) seleccionado(s)
                            </p>
                            <p class="text-base sm:text-lg font-bold text-blue-700">
                                Total: €<span x-text="calcularTotalSeleccionado().toFixed(2)"></span>
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2">
                        <button @click="marcarSeleccionadosComoPagados"
                                class="flex-1 bg-green-600 text-white px-4 py-2.5 rounded text-sm hover:bg-green-700 font-medium">
                            💰 Marcar PAGADO
                        </button>
                        <button @click="eliminarProductosSeleccionados"
                                class="flex-1 bg-red-600 text-white px-4 py-2.5 rounded text-sm hover:bg-red-700 font-medium">
                            🗑️ Eliminar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Panel Derecho: Resumen y Acciones -->
            <div class="space-y-3 sm:space-y-4 order-1 lg:order-2">
                <!-- Resumen de Totales -->
                <div class="bg-white border rounded-lg p-3 sm:p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Resumen de Cuenta</h3>

                    <div class="space-y-2 text-xs sm:text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Original:</span>
                            <span class="font-semibold" x-text="'€' + (ticketActual?.total ?? 0).toFixed(2)"></span>
                        </div>

                        <div class="flex justify-between text-green-700">
                            <span>Ya Pagado:</span>
                            <span class="font-semibold" x-text="'€' + calcularTotalPagado().toFixed(2)"></span>
                        </div>

                        <hr class="my-2">

                        <div class="flex justify-between text-base sm:text-lg font-bold text-blue-700">
                            <span>Pendiente:</span>
                            <span x-text="'€' + calcularTotalPendiente().toFixed(2)"></span>
                        </div>
                    </div>
                </div>

                <!-- Calculadora de Cambio -->
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-200 rounded-lg p-3 sm:p-4" x-data="{ pagaCon: '' }">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                        <span class="text-base sm:text-lg">💰</span>
                        Calculadora de Cambio
                    </h3>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Cliente paga con:</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-semibold">€</span>
                                <input
                                    type="number"
                                    step="0.01"
                                    x-model="pagaCon"
                                    @input="pagaCon = $event.target.value"
                                    class="w-full pl-8 pr-3 py-2 border-2 border-green-300 rounded-lg text-lg font-bold text-gray-800 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition"
                                    placeholder="0.00">
                            </div>
                        </div>

                        <!-- Botones rápidos de valores comunes -->
                        <div class="grid grid-cols-4 gap-2">
                            <button @click="pagaCon = '10'" class="bg-green-100 hover:bg-green-200 text-green-800 py-1.5 rounded text-xs font-semibold transition">€10</button>
                            <button @click="pagaCon = '20'" class="bg-green-100 hover:bg-green-200 text-green-800 py-1.5 rounded text-xs font-semibold transition">€20</button>
                            <button @click="pagaCon = '50'" class="bg-green-100 hover:bg-green-200 text-green-800 py-1.5 rounded text-xs font-semibold transition">€50</button>
                            <button @click="pagaCon = calcularTotalPendiente().toFixed(2)" class="bg-blue-100 hover:bg-blue-200 text-blue-800 py-1.5 rounded text-xs font-semibold transition">Exacto</button>
                        </div>

                        <!-- Mostrar el cambio -->
                        <template x-if="pagaCon && parseFloat(pagaCon) > 0">
                            <div class="mt-3 pt-3 border-t-2 border-green-300">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-700">Cambio a devolver:</span>
                                    <span
                                        class="text-2xl font-bold"
                                        :class="parseFloat(pagaCon) >= calcularTotalPendiente() ? 'text-green-600' : 'text-red-600'"
                                        x-text="'€' + Math.max(0, parseFloat(pagaCon) - calcularTotalPendiente()).toFixed(2)">
                                    </span>
                                </div>
                                <template x-if="parseFloat(pagaCon) < calcularTotalPendiente()">
                                    <div class="mt-2 bg-red-100 border border-red-300 rounded px-3 py-2 text-xs text-red-700">
                                        ⚠️ Falta: €<span x-text="(calcularTotalPendiente() - parseFloat(pagaCon)).toFixed(2)"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Acciones del Ticket -->
                <div class="bg-white border rounded-lg p-3 sm:p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Acciones del Ticket</h3>

                    <label class="block text-xs mb-1">Enviar por Email</label>
                    <div class="flex items-center gap-2 mb-3 sm:mb-4">
                        <input type="email" x-model="emailDestino"
                               class="flex-1 px-2 py-2 border rounded text-sm bg-white text-gray-700"
                               placeholder="cliente@email.com">
                        <button @click="enviarTicketEmail"
                                class="bg-blue-500 text-white px-3 py-2 rounded text-sm hover:bg-blue-600 whitespace-nowrap">
                            ✉️
                        </button>
                    </div>

                    <button @click="generarPDFTicket"
                            class="w-full bg-gray-800 text-white py-2.5 rounded text-sm hover:bg-gray-900 flex items-center justify-center gap-2 mb-2">
                        🧾 Descargar PDF
                    </button>

                    <button @click="mostrarTicket = false; mostrarModal = true"
                            class="w-full bg-gray-200 text-gray-700 py-2.5 rounded text-sm hover:bg-gray-300">
                        ← Volver al TPV
                    </button>
                </div>

                <!-- Botón de Cierre -->
                <button @click="cerrarMesa"
                        :disabled="calcularTotalPendiente() > 0.01"
                        class="w-full bg-green-600 text-white py-3 sm:py-3.5 rounded-lg text-sm sm:text-base hover:bg-green-700 font-semibold disabled:bg-gray-300 disabled:cursor-not-allowed sticky bottom-0 sm:static shadow-lg sm:shadow-none"
                        :class="{'opacity-50': calcularTotalPendiente() > 0.01}">
                    <template x-if="calcularTotalPendiente() > 0.01">
                        <span class="text-xs sm:text-sm">⚠️ Quedan €<span x-text="calcularTotalPendiente().toFixed(2)"></span> por pagar</span>
                    </template>
                    <template x-if="calcularTotalPendiente() <= 0.01">
                        <span>✅ Finalizar y Cerrar Mesa</span>
                    </template>
                </button>
            </div>
        </div>
    </div>
</div>
