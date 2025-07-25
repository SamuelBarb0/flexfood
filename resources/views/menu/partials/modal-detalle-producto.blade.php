<!-- Modal Detalle Producto -->
<div x-show="modalProducto" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center" x-cloak>
    <div class="bg-white rounded-lg w-full max-w-md p-6 relative" @click.away="modalProducto = false">
        <button @click="modalProducto = false" class="absolute top-2 right-3 text-gray-500 hover:text-red-500 text-xl">×</button>

        <template x-if="productoSeleccionado">
            <div>
                <img :src="productoSeleccionado.imagen" alt="" class="w-full h-48 object-contain mb-4 rounded">
                <h2 class="text-xl font-bold text-[#153958]" x-text="productoSeleccionado.nombre"></h2>
                <p class="text-gray-600 text-sm my-2" x-text="productoSeleccionado.descripcion"></p>

                <!-- Precio total con adiciones -->
                <p class="text-[#3CB28B] text-lg font-semibold mb-4">
                    €<span x-text="calcularPrecioTotal().toFixed(2)"></span>
                </p>

                <!-- Mostrar adiciones solo si existen -->
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
                                <span class="text-sm text-gray-700" x-text="`${adicion.nombre} (€${parseFloat(adicion.precio).toFixed(2)})`"></span>
                            </label>
                        </template>
                    </div>
                </template>

                <button @click="agregarConAdiciones()" class="mt-4 w-full bg-[#3CB28B] text-white py-2 rounded hover:bg-[#2e9e75]">
                    Agregar al carrito
                </button>
            </div>
        </template>
    </div>
</div>
