<script>
    window.RESTAURANTE_NOMBRE = @json($restauranteNombre ?? $restaurante->nombre ?? 'Restaurante');
</script>

<!-- Modal Ticket Bonito -->
<div x-show="mostrarTicket"
    class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center" x-cloak>
    <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full p-6 relative" @click.away="mostrarTicket = false">

        <!-- Título -->
        <div class="flex justify-between items-start mb-4">
            <h2 class="text-xl font-semibold text-gray-800">
                Resumen y Cierre - Mesa <span x-text="ticketActual?.mesa ?? ''"></span>
            </h2>
            <button @click="mostrarTicket = false" class="text-gray-400 hover:text-red-500 text-xl font-bold">×</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Ticket visual -->
            <div id="ticket-printable"
                class="bg-white text-xs font-mono p-4 w-[300px] mx-auto border border-dashed rounded"
                style="line-height: 1.4;" x-show="ticketActual">

                <div class="text-center mb-2">
                    <!-- 👇 Dinámico: primero ticketActual.restaurante_nombre, si no, el global del Blade -->
                    <p class="font-bold text-sm"
                       x-text="ticketActual?.restaurante_nombre ?? window.RESTAURANTE_NOMBRE"></p>

                    <p>Recibo Mesa <span x-text="ticketActual?.mesa ?? ''"></span></p>
                    <p class="text-gray-500" x-text="'Fecha: ' + (ticketActual?.fecha ?? '')"></p>
                </div>

                <hr class="border-t border-dashed border-gray-400 my-2">

                <div class="flex justify-between font-bold mb-1">
                    <span>Cant.</span>
                    <span class="flex-1 text-center">Artículo</span>
                    <span>Total</span>
                </div>

                <template x-for="item in ticketActual?.productos ?? []" :key="item.nombre + JSON.stringify(item.adiciones)">
                    <div class="text-[12px] py-1 border-b border-dashed border-gray-300">
                        <div class="flex justify-between">
                            <span class="w-6 text-left" x-text="item.cantidad"></span>
                            <span class="flex-1 text-center truncate" x-text="item.nombre"></span>
                            <span
                              x-text="((parseFloat(item.precio_base ?? item.precio) + (item.adiciones?.reduce((sum, a) => sum + parseFloat(a.precio), 0) || 0)) * item.cantidad).toFixed(2)">
                            </span>
                        </div>

                        <!-- Mostrar adiciones si existen -->
                        <template x-if="item.adiciones && item.adiciones.length > 0">
                            <ul class="pl-8 text-[11px] text-gray-500 list-disc mt-1">
                                <template x-for="adic in item.adiciones" :key="adic.id">
                                    <li>
                                        <span x-text="adic.nombre"></span>
                                        <span x-text="`(+€${parseFloat(adic.precio).toFixed(2)})`"></span>
                                    </li>
                                </template>
                            </ul>
                        </template>
                    </div>
                </template>

                <hr class="border-t border-dashed border-gray-400 my-2">

                <!-- 🧮 Totales con IVA -->
                <template x-if="ticketActual">
                    <div class="space-y-1">
                        <div class="flex justify-between text-sm">
                            <span>Subtotal</span>
                            <span x-text="(ticketActual.total ?? 0).toFixed(2) + ' €'"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span>IVA (10%)</span>
                            <span x-text="(((ticketActual.total ?? 0) * 0.10)).toFixed(2) + ' €'"></span>
                        </div>
                        <div class="flex justify-between font-bold mt-2 text-sm">
                            <span>TOTAL</span>
                            <span x-text="(((ticketActual.total ?? 0) * 1.10)).toFixed(2) + ' €'"></span>
                        </div>
                    </div>
                </template>

                <p class="text-center mt-3 text-gray-500 text-[12px]">¡Gracias por su visita!</p>
            </div>

            <!-- Acciones -->
            <div class="bg-white border rounded-lg p-4 flex flex-col justify-between">
                <div>
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
                            class="w-full bg-gray-800 text-white py-2 rounded text-sm hover:bg-gray-900 flex items-center justify-center gap-2">
                        🧾 Descargar PDF del Ticket
                    </button>
                </div>

                <div class="mt-6 flex justify-between">
                    <button @click="mostrarTicket = false; mostrarModal = true"
                            class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-300">
                        Volver al TPV
                    </button>
                    <button @click="cerrarMesa"
                            class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">
                        ✅ Finalizar y Cerrar Mesa
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
