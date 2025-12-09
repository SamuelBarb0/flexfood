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
                               placeholder="cliente@email.com"
                               :disabled="!botonesTicketHabilitados">
                        <button @click="enviarTicketEmail"
                                :disabled="!botonesTicketHabilitados"
                                class="bg-blue-500 text-white px-3 py-2 rounded text-sm hover:bg-blue-600 whitespace-nowrap disabled:bg-gray-300 disabled:cursor-not-allowed">
                            ✉️
                        </button>
                    </div>

                    <!-- Botón Ticket PDF -->
                    <button @click="generarPDFTicket"
                            :disabled="!botonesTicketHabilitados"
                            class="w-full py-2.5 rounded text-sm flex items-center justify-center gap-2 mb-2 transition-colors"
                            :class="botonesTicketHabilitados ? 'bg-gray-800 text-white hover:bg-gray-900 cursor-pointer' : 'bg-gray-300 text-gray-500 cursor-not-allowed'">
                        🧾 Descargar Ticket PDF
                    </button>

                    <!-- Botón Factura PDF (solo si existe factura) -->
                    <button x-show="ticketActual?.factura_id"
                            @click="descargarFacturaPDF"
                            :disabled="!botonesTicketHabilitados"
                            class="w-full py-2.5 rounded text-sm flex items-center justify-center gap-2 mb-2 transition-colors"
                            :class="botonesTicketHabilitados ? 'bg-blue-600 text-white hover:bg-blue-700 cursor-pointer' : 'bg-gray-300 text-gray-500 cursor-not-allowed'">
                        📄 Descargar Factura PDF
                    </button>

                    <!-- Botón Generar Factura (solo si NO existe factura) -->
                    <button x-show="!ticketActual?.factura_id"
                            @click="mostrarFormularioFactura = true"
                            :disabled="!botonesTicketHabilitados"
                            class="w-full py-2.5 rounded text-sm flex items-center justify-center gap-2 mb-2 font-semibold transition-colors"
                            :class="botonesTicketHabilitados ? 'bg-green-600 text-white hover:bg-green-700 cursor-pointer' : 'bg-gray-300 text-gray-500 cursor-not-allowed'">
                        📄 Generar Factura
                    </button>

                    <button @click="mostrarTicket = false; mostrarModal = true"
                            class="w-full bg-gray-200 text-gray-700 py-2.5 rounded text-sm hover:bg-gray-300">
                        ← Volver al TPV
                    </button>
                </div>

                <!-- Selector de Serie de Facturación -->
                @if($restaurante->fiscal_habilitado && $restaurante->facturacion_automatica)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-3">
                    <label class="block text-xs font-semibold text-gray-700 mb-2">
                        📋 Serie de Facturación
                    </label>
                    <select x-model="serieFacturacionId"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-400 focus:ring-0 text-sm">
                        @foreach($restaurante->seriesFacturacion()->activas()->get() as $serie)
                            <option value="{{ $serie->id }}" {{ $serie->es_principal ? 'selected' : '' }}>
                                {{ $serie->nombre }} ({{ $serie->codigo_serie }})
                                @if($serie->es_principal) ⭐ Principal @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        Se generará una factura automáticamente con esta serie
                    </p>
                </div>
                @endif

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

                <!-- Estado de VeriFactu -->
                @if($restaurante->fiscal_habilitado)
                <div x-show="procesandoVeriFactu || veriFactuCompleto"
                     x-transition
                     class="mt-3 p-3 rounded-lg border"
                     :class="veriFactuCompleto ? 'bg-green-50 border-green-200' : 'bg-blue-50 border-blue-200'">

                    <!-- Procesando -->
                    <div x-show="procesandoVeriFactu && !veriFactuCompleto" class="flex items-center gap-2">
                        <div class="animate-spin h-4 w-4 border-2 border-blue-600 border-t-transparent rounded-full"></div>
                        <span class="text-sm font-medium text-blue-700">Procesando VeriFactu...</span>
                    </div>

                    <!-- Completado -->
                    <div x-show="veriFactuCompleto" class="flex items-center gap-2">
                        <span class="text-lg">✅</span>
                        <span class="text-sm font-semibold text-green-700">VeriFactu completado</span>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal: Formulario de Factura con Datos del Cliente -->
    <div x-show="mostrarFormularioFactura"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999] p-4"
         @click="mostrarFormularioFactura = false">
        <div @click.stop class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-800">📄 Generar Factura</h3>
                <button @click="mostrarFormularioFactura = false" class="text-gray-500 hover:text-gray-700 text-2xl">
                    &times;
                </button>
            </div>

            <form @submit.prevent="guardarFactura" class="p-6 space-y-4">
                <!-- Nombre y apellidos / Razón Social -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Nombre y apellidos / Razón social <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           x-model="datosFactura.razon_social"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Ej: Juan Pérez García o Empresa S.L.">
                </div>

                <!-- NIF o CIF -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        NIF o CIF <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           x-model="datosFactura.nif_cif"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Ej: 12345678Z o B12345678">
                </div>

                <!-- Email (opcional) -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Email
                    </label>
                    <input type="email"
                           x-model="datosFactura.email"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="cliente@ejemplo.com">
                    <p class="text-xs text-gray-500 mt-1">Se enviará la factura automáticamente a este email</p>
                </div>

                <!-- Domicilio fiscal -->
                <div class="border-t pt-4">
                    <h4 class="text-sm font-bold text-gray-700 mb-3">Domicilio Fiscal</h4>

                    <!-- Dirección -->
                    <div class="mb-3">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Dirección <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               x-model="datosFactura.direccion"
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Calle, número, piso, puerta...">
                    </div>

                    <!-- Provincia, Localidad, Código Postal (en 3 columnas) -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Provincia <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   x-model="datosFactura.provincia"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Ej: Madrid">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Localidad <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   x-model="datosFactura.municipio"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Ej: Madrid">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Código Postal <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   x-model="datosFactura.codigo_postal"
                                   required
                                   pattern="[0-9]{5}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="28001">
                        </div>
                    </div>

                    <!-- País -->
                    <div class="mt-3">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            País (si extranjero o UE)
                        </label>
                        <select x-model="datosFactura.pais"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <!-- España -->
                            <option value="ES" selected>España</option>

                            <!-- Unión Europea -->
                            <optgroup label="🇪🇺 Unión Europea">
                                <option value="AT">Austria</option>
                                <option value="BE">Bélgica</option>
                                <option value="BG">Bulgaria</option>
                                <option value="HR">Croacia</option>
                                <option value="CY">Chipre</option>
                                <option value="CZ">República Checa</option>
                                <option value="DK">Dinamarca</option>
                                <option value="EE">Estonia</option>
                                <option value="FI">Finlandia</option>
                                <option value="FR">Francia</option>
                                <option value="DE">Alemania</option>
                                <option value="GR">Grecia</option>
                                <option value="HU">Hungría</option>
                                <option value="IE">Irlanda</option>
                                <option value="IT">Italia</option>
                                <option value="LV">Letonia</option>
                                <option value="LT">Lituania</option>
                                <option value="LU">Luxemburgo</option>
                                <option value="MT">Malta</option>
                                <option value="NL">Países Bajos</option>
                                <option value="PL">Polonia</option>
                                <option value="PT">Portugal</option>
                                <option value="RO">Rumanía</option>
                                <option value="SK">Eslovaquia</option>
                                <option value="SI">Eslovenia</option>
                                <option value="SE">Suecia</option>
                            </optgroup>

                            <!-- Europa (No UE) -->
                            <optgroup label="🌍 Europa (No UE)">
                                <option value="AD">Andorra</option>
                                <option value="AL">Albania</option>
                                <option value="BY">Bielorrusia</option>
                                <option value="BA">Bosnia y Herzegovina</option>
                                <option value="GB">Reino Unido</option>
                                <option value="IS">Islandia</option>
                                <option value="XK">Kosovo</option>
                                <option value="LI">Liechtenstein</option>
                                <option value="MK">Macedonia del Norte</option>
                                <option value="MD">Moldavia</option>
                                <option value="MC">Mónaco</option>
                                <option value="ME">Montenegro</option>
                                <option value="NO">Noruega</option>
                                <option value="RS">Serbia</option>
                                <option value="CH">Suiza</option>
                                <option value="UA">Ucrania</option>
                                <option value="VA">Ciudad del Vaticano</option>
                            </optgroup>

                            <!-- América -->
                            <optgroup label="🌎 América">
                                <option value="AR">Argentina</option>
                                <option value="BO">Bolivia</option>
                                <option value="BR">Brasil</option>
                                <option value="CA">Canadá</option>
                                <option value="CL">Chile</option>
                                <option value="CO">Colombia</option>
                                <option value="CR">Costa Rica</option>
                                <option value="CU">Cuba</option>
                                <option value="DO">República Dominicana</option>
                                <option value="EC">Ecuador</option>
                                <option value="SV">El Salvador</option>
                                <option value="GT">Guatemala</option>
                                <option value="HN">Honduras</option>
                                <option value="MX">México</option>
                                <option value="NI">Nicaragua</option>
                                <option value="PA">Panamá</option>
                                <option value="PY">Paraguay</option>
                                <option value="PE">Perú</option>
                                <option value="PR">Puerto Rico</option>
                                <option value="UY">Uruguay</option>
                                <option value="US">Estados Unidos</option>
                                <option value="VE">Venezuela</option>
                            </optgroup>

                            <!-- Asia -->
                            <optgroup label="🌏 Asia">
                                <option value="CN">China</option>
                                <option value="IN">India</option>
                                <option value="ID">Indonesia</option>
                                <option value="JP">Japón</option>
                                <option value="MY">Malasia</option>
                                <option value="PK">Pakistán</option>
                                <option value="PH">Filipinas</option>
                                <option value="SG">Singapur</option>
                                <option value="KR">Corea del Sur</option>
                                <option value="TH">Tailandia</option>
                                <option value="TR">Turquía</option>
                                <option value="VN">Vietnam</option>
                            </optgroup>

                            <!-- África -->
                            <optgroup label="🌍 África">
                                <option value="DZ">Argelia</option>
                                <option value="EG">Egipto</option>
                                <option value="MA">Marruecos</option>
                                <option value="NG">Nigeria</option>
                                <option value="ZA">Sudáfrica</option>
                                <option value="TN">Túnez</option>
                            </optgroup>

                            <!-- Oceanía -->
                            <optgroup label="🌏 Oceanía">
                                <option value="AU">Australia</option>
                                <option value="NZ">Nueva Zelanda</option>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="flex gap-3 pt-4 border-t">
                    <button type="button"
                            @click="mostrarFormularioFactura = false"
                            class="flex-1 px-4 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-semibold">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                        Guardar Factura
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
