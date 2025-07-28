<!-- Modal Ticket Bonito -->
<div x-show="mostrarTicket" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center" x-cloak>
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
                    <p class="font-bold text-sm">FlexFood</p>
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
                            <span x-text="((parseFloat(item.precio_base ?? item.precio) + (item.adiciones?.reduce((sum, a) => sum + parseFloat(a.precio), 0) || 0)) * item.cantidad).toFixed(2)"></span>
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

                <div class="flex justify-between font-bold mt-2 text-sm">
                    <span>TOTAL</span>
                    <span x-text="ticketActual?.total?.toFixed(2) + ' €' ?? ''"></span>
                </div>

                <p class="text-center mt-3 text-gray-500 text-[12px]">¡Gracias por su visita!</p>
            </div>

            <!-- Acciones -->
            <div class="bg-white border rounded-lg p-4 flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Acciones del Ticket</h3>

                    <label class="block text-xs mb-1">Enviar por Email</label>
                    <div class="flex items-center space-x-2 mb-4">
                        <input type="email" disabled value="cliente@email.com"
                            class="w-full px-2 py-1 border rounded text-sm bg-gray-100 text-gray-500 cursor-not-allowed">
                        <button disabled
                            class="bg-gray-400 text-white px-2 py-1 rounded text-xs cursor-not-allowed">
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