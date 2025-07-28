<!-- Modal TPV -->
<div
    x-show="mostrarModal"
    class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center"
    x-cloak>
    <div class="bg-white rounded-lg w-full max-w-4xl p-6 relative" @click.self="mostrarModal = false">
        <button @click="mostrarModal = false" class="absolute top-3 right-4 text-gray-500 text-xl">×</button>

        <h2 class="text-xl font-bold mb-4">TPV - Mesa <span x-text="mesaSeleccionada"></span></h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Cuenta Actual -->
            <div class="bg-gray-50 p-4 rounded border">
                <h3 class="font-semibold text-gray-700 mb-2">Cuenta Actual</h3>

                <template x-if="cuentaActual.length === 0">
                    <div class="text-gray-400 text-sm italic">No hay productos aún.</div>
                </template>

                <template x-for="(item, index) in cuentaActual" :key="item.nombre + JSON.stringify(item.adiciones)">
                    <div class="mb-2 text-sm text-gray-800">
                        <div class="flex justify-between items-center">
                            <span x-text="`${item.cantidad}x ${item.nombre}`"></span>
                            <div class="flex items-center space-x-2">
                                <span x-text="`${((parseFloat(item.precio_base ?? item.precio) + (item.adiciones?.reduce((sum, a) => sum + parseFloat(a.precio), 0) || 0)) * item.cantidad).toFixed(2)} €`"></span>
                                <button
                                    @click="item.cantidad > 1 ? item.cantidad-- : cuentaActual.splice(index, 1)"
                                    class="text-red-500 hover:text-red-700 ml-2 text-sm"
                                    title="Quitar uno">🗑️</button>
                            </div>
                        </div>

                        <!-- Mostrar precio base -->
                        <div class="ml-2 text-xs text-gray-500">
                            Precio base: €<span x-text="parseFloat(item.precio_base ?? item.precio).toFixed(2)"></span>
                        </div>

                        <!-- Mostrar adiciones si existen -->
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
                    </div>
                </template>

                <div class="border-t my-3"></div>

                <div class="text-right font-bold text-lg">
                    Total: <span x-text="totalCuenta.toFixed(2) + ' €'"></span>
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
                                    @click="agregarProducto(producto)">
                                    <span x-text="producto.nombre" class="text-sm"></span>
                                    <span x-text="parseFloat(producto.precio).toFixed(2) + '€'" class="text-sm font-medium"></span>
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

        <div class="mt-6 flex justify-end space-x-2">
            <button @click="mostrarModal = false" class="bg-gray-200 px-4 py-2 rounded text-gray-700 text-sm">Cancelar</button>
            <button @click="gestionarTicket" class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Gestionar Ticket</button>
        </div>
    </div>
</div>