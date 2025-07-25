@extends('layouts.app')

@section('title', 'Menú Público')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-[#153958] mb-6 text-center">Nuestro Menú</h1>

    <div x-data="menuCarrito()" class="relative">
        @foreach ($categorias as $categoria)
            @if ($categoria->productos->where('disponible', true)->count())
                <div class="mb-10">
                    <h2 class="text-2xl font-semibold text-[#3CB28B] mb-4">{{ $categoria->nombre }}</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        @foreach ($categoria->productos->where('disponible', true) as $producto)
                            <div class="border rounded-lg shadow-sm bg-white p-4 flex flex-col justify-between">
                                @if ($producto->imagen)
                                    <img src="{{ asset('images/' . $producto->imagen) }}"
                                        class="h-32 w-full object-cover rounded mb-3" alt="{{ $producto->nombre }}">
                                @endif

                                <h3 class="text-lg font-bold text-[#153958]">{{ $producto->nombre }}</h3>
                                <p class="text-sm text-gray-600 mb-2">{{ $producto->descripcion }}</p>
                                <p class="text-md text-[#153958] font-semibold mb-2">€{{ number_format($producto->precio, 2) }}</p>

                                <div class="mt-auto flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <button @click="quitarDelCarrito({{ $producto->id }})"
                                            class="bg-[#153958] text-white rounded-full w-7 h-7 text-sm flex items-center justify-center">-</button>
                                        <span x-text="cantidadEnCarrito({{ $producto->id }})"
                                            class="min-w-[20px] text-sm text-[#153958] font-semibold">0</span>
                                        <button @click="agregarAlCarrito({{ $producto->id }}, '{{ $producto->nombre }}', {{ $producto->precio }})"
                                            class="bg-[#3CB28B] text-white rounded-full w-7 h-7 text-sm flex items-center justify-center">+</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach

        <!-- Botón para abrir carrito -->
        <div class="fixed bottom-6 right-6 z-50">
            <button @click="mostrarCarrito = true"
                class="bg-[#153958] hover:bg-[#0f2c47] text-white px-4 py-3 rounded-full shadow-lg flex items-center gap-2">
                🛒 Ver carrito
                <span x-text="totalCantidad" class="bg-[#3CB28B] text-white text-xs font-bold px-2 py-1 rounded-full">0</span>
            </button>
        </div>

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

            <template x-for="item in carrito" :key="item.id">
                <div class="border-b py-3 flex justify-between items-center">
                    <div>
                        <p class="text-sm font-semibold text-[#153958]" x-text="item.nombre"></p>
                        <p class="text-xs text-gray-500">€<span x-text="item.precio.toFixed(2)"></span> x <span x-text="item.cantidad"></span></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="quitarDelCarrito(item.id)"
                            class="bg-[#153958] text-white rounded-full w-6 h-6 text-sm flex items-center justify-center">-</button>
                        <button @click="agregarAlCarrito(item.id, item.nombre, item.precio)"
                            class="bg-[#3CB28B] text-white rounded-full w-6 h-6 text-sm flex items-center justify-center">+</button>
                    </div>
                </div>
            </template>

            <div class="mt-6 border-t pt-4">
                <p class="text-lg font-bold text-[#153958]">Total: €<span x-text="totalPrecio.toFixed(2)"></span></p>
                <button class="mt-4 w-full bg-[#3CB28B] text-white py-2 rounded hover:bg-[#2e9e75]">Finalizar pedido</button>
            </div>
        </div>
    </div>
</div>

<script>
    function menuCarrito() {
        return {
            carrito: [],
            mostrarCarrito: false,

            agregarAlCarrito(id, nombre, precio) {
                const existente = this.carrito.find(p => p.id === id);
                if (existente) {
                    existente.cantidad++;
                } else {
                    this.carrito.push({ id, nombre, precio, cantidad: 1 });
                }
            },

            quitarDelCarrito(id) {
                const producto = this.carrito.find(p => p.id === id);
                if (producto && producto.cantidad > 1) {
                    producto.cantidad--;
                } else {
                    this.carrito = this.carrito.filter(p => p.id !== id);
                }
            },

            cantidadEnCarrito(id) {
                const prod = this.carrito.find(p => p.id === id);
                return prod ? prod.cantidad : 0;
            },

            get totalCantidad() {
                return this.carrito.reduce((acc, item) => acc + item.cantidad, 0);
            },

            get totalPrecio() {
                return this.carrito.reduce((acc, item) => acc + (item.precio * item.cantidad), 0);
            }
        }
    }
</script>
@endsection
