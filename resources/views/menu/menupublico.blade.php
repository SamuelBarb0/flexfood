@extends('layouts.app')

@section('title', 'Menú Público')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8" x-data="menuCarrito()" x-init="init">

    {{-- Logo y título --}}
    <div class="text-center mb-4">
        <img src="{{ asset('images/flexfood.png') }}" alt="Logo FlexFood" class="mx-auto h-20 mb-2">
    </div>
    <h1 class="text-3xl font-bold text-[#0C3558] mb-6 text-center">Nuestro Menú</h1>

    {{-- ScrollSpy horizontal de categorías --}}
    <div class="sticky top-0 z-40 bg-white py-3 mb-6 overflow-x-auto whitespace-nowrap flex gap-3 border-b shadow-sm">
        @foreach ($categorias as $categoria)
            <a href="#categoria-{{ $categoria->id }}"
               class="px-4 py-2 rounded-full text-sm font-semibold transition-colors duration-300 bg-[#0C3558] text-white hover:bg-[#3CB28B]">
                {{ $categoria->nombre }}
            </a>
        @endforeach
    </div>

    {{-- Listado de productos por categoría --}}
    @foreach ($categorias as $categoria)
        @if ($categoria->productos->where('disponible', true)->count())
            <div class="mb-10 opacity-0 animate-fadeIn" id="categoria-{{ $categoria->id }}">
                <h2 class="text-2xl font-semibold text-[#3CB28B] mb-4">{{ $categoria->nombre }}</h2>

                <div class="flex flex-col gap-6">
                    @foreach ($categoria->productos->where('disponible', true) as $producto)
                        <div class="border border-gray-200 rounded-xl p-4 shadow-sm bg-white text-[#0C3558] transition-transform duration-300 transform hover:scale-105 group">
                            @if ($producto->imagen)
                                <img src="{{ asset('images/' . $producto->imagen) }}"
                                     alt="{{ $producto->nombre }}"
                                     class="rounded-lg w-full h-40 object-cover mb-4 transition-all duration-300 group-hover:opacity-90">
                            @endif

                            <h3 class="text-lg font-bold uppercase">{{ $producto->nombre }}</h3>

                            <p class="text-sm text-gray-600 truncate mb-2">
                                {{ \Illuminate\Support\Str::limit($producto->descripcion, 60) }}
                            </p>

                            <p class="text-md font-semibold mb-3">€{{ number_format($producto->precio, 2) }}</p>

                            <div class="flex justify-between items-center">
                                <div class="text-[#0C3558] text-xl opacity-0 pointer-events-none">🤍</div> {{-- Placeholder oculto --}}
                                <button
                                    @click='abrirDetalle(JSON.parse(`{!! json_encode([
                                        "id" => $producto->id,
                                        "nombre" => $producto->nombre,
                                        "descripcion" => $producto->descripcion,
                                        "precio" => (float) $producto->precio,
                                        "imagen" => asset("images/" . $producto->imagen),
                                        "adiciones_disponibles" => $producto->adiciones,
                                    ]) !!}`))'
                                    class="bg-[#0C3558] hover:bg-[#3CB28B] transition-colors text-white font-bold rounded-full px-6 py-1 text-sm">
                                    Añadir
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach

    {{-- Modal y carrito --}}
    @include('menu.partials.modal-detalle-producto')
    @include('menu.partials.modal-gracias')
    @include('menu.partials.modal-carrito')

    {{-- Menú inferior fijo tipo app --}}
<div class="fixed bottom-0 left-0 right-0 bg-[#0C3558] text-white flex justify-around items-center py-2 z-50 border-t">
    <button @click="mostrarVideos = true" class="flex flex-col items-center text-sm focus:outline-none">
        <span class="text-lg">🎥</span>
        <span>Video</span>
    </button>
    <button onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
            class="flex flex-col items-center text-sm focus:outline-none">
        <span class="text-lg">📋</span>
        <span>Menú</span>
    </button>
    <button @click="mostrarCarrito = true"
            class="flex flex-col items-center text-sm focus:outline-none relative">
        <div class="relative">
            <span class="text-lg">🛒</span>
            <!-- Contador de productos -->
            <span x-show="totalCantidad > 0" 
                  x-text="totalCantidad"
                  class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold min-w-[20px]">
            </span>
        </div>
        <span>Mi pedido</span>
    </button>
</div>

<div
    x-show="mostrarVideos"
    x-data="scrollSpyCategorias()"
    x-init="init"
    @scroll="onScroll"
    class="fixed inset-0 z-50 bg-black overflow-y-scroll snap-y snap-mandatory scroll-smooth"
    id="contenedorVideos"
>

    {{-- Botón cerrar más discreto pero siempre visible --}}
    <button @click="mostrarVideos = false"
            class="fixed top-3 right-3 z-[9999] w-8 h-8 flex items-center justify-center text-white bg-black/40 hover:bg-red-500/80 rounded-full text-lg backdrop-blur-md border border-white/20 shadow-lg transition-all duration-300 hover:scale-110">
        ✕
    </button>

    {{-- Scroll horizontal de categorías con diseño mejorado --}}
    <div class="sticky top-0 z-[60] bg-gradient-to-r from-black/90 via-black/80 to-black/90 backdrop-blur-lg py-4 px-4 border-b border-white/10">
        <div class="flex items-center justify-center">
            <div class="flex gap-2 overflow-x-auto max-w-full scrollbar-hide px-1">
                @foreach ($categorias as $categoria)
                    @if ($categoria->productos->where('disponible', true)->count())
                        <a href="#"
                           @click.prevent="scrollToCategoria('{{ $categoria->id }}')"
                           :class="categoriaActiva === '{{ $categoria->id }}'
                                   ? 'bg-gradient-to-r from-[#3CB28B] to-[#2A9C75] text-white shadow-lg shadow-[#3CB28B]/30 border-[#3CB28B]/50'
                                   : 'bg-white/10 text-white/90 hover:bg-gradient-to-r hover:from-[#3CB28B]/80 hover:to-[#2A9C75]/80 border-white/20'"
                           class="flex-shrink-0 px-5 py-2.5 rounded-full text-sm font-medium transition-all duration-300 border backdrop-blur-sm hover:scale-105 hover:shadow-md">
                            {{ $categoria->nombre }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    {{-- Productos de todas las categorías --}}
    @foreach ($categorias as $categoria)
        @php
            $productosConMediaDisponibles = $categoria->productos->filter(fn ($p) => 
                ($p->video || $p->imagen) && $p->disponible == true
            );
        @endphp

        @if ($productosConMediaDisponibles->count())
            <div id="categoria-{{ $categoria->id }}">
                @foreach ($productosConMediaDisponibles as $producto)
                    <div class="min-h-screen w-full flex flex-col justify-between snap-start relative">
                        {{-- VIDEO o IMAGEN --}}
                        @if ($producto->video)
                            <video
                                src="{{ asset('images/' . $producto->video) }}"
                                autoplay
                                muted
                                loop
                                playsinline
                                class="absolute top-0 left-0 w-full h-full object-cover z-0">
                            </video>
                        @elseif ($producto->imagen)
                            <img
                                src="{{ asset('images/' . $producto->imagen) }}"
                                alt="{{ $producto->nombre }}"
                                class="absolute top-0 left-0 w-full h-full object-cover z-0">
                        @endif

                        {{-- INFORMACIÓN DEL PRODUCTO con diseño mejorado --}}
                        <div class="relative z-10 bg-gradient-to-t from-black/90 via-black/60 to-transparent p-6 text-white mt-auto">
                            <div class="flex justify-between items-start mb-3">
                                <h2 class="text-xl font-bold uppercase tracking-wide">{{ $producto->nombre }}</h2>
                                <span class="bg-gradient-to-r from-[#3CB28B] to-[#2A9C75] text-white text-sm font-bold px-3 py-1.5 rounded-lg shadow-lg">
                                    €{{ number_format($producto->precio, 2) }}
                                </span>
                            </div>

                            <p class="text-sm mb-4 text-white/90 leading-relaxed">{{ \Illuminate\Support\Str::limit($producto->descripcion, 100) }}</p>

                            <div class="flex justify-center items-center">
                                <button
                                    @click='abrirDetalle(JSON.parse(`{!! json_encode([
                                        "id" => $producto->id,
                                        "nombre" => $producto->nombre,
                                        "descripcion" => $producto->descripcion,
                                        "precio" => (float) $producto->precio,
                                        "imagen" => $producto->imagen ? asset("images/" . $producto->imagen) : null,
                                        "adiciones_disponibles" => $producto->adiciones,
                                    ]) !!}`)); mostrarVideos = false'
                                    class="bg-gradient-to-r from-[#3CB28B] to-[#2A9C75] hover:from-[#2A9C75] hover:to-[#238B63] text-white font-bold px-8 py-3 rounded-full shadow-lg shadow-[#3CB28B]/30 transition-all duration-300 hover:scale-105 hover:shadow-xl border border-white/20 backdrop-blur-sm flex items-center gap-2">
                                    <span class="text-lg">➕</span>
                                    <span>Añadir al carrito</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endforeach

</div>

{{-- Alpine scrollspy script --}}
<script>
function scrollSpyCategorias() {
    return {
        categoriaActiva: null,
        categorias: [],
        init() {
            this.categorias = [...document.querySelectorAll('[id^="categoria-"]')];
            this.onScroll();
        },
        scrollToCategoria(id) {
            // Buscar específicamente en el contenedor de VIDEOS, no en el menú normal
            const contenedor = document.getElementById('contenedorVideos');
            const categoriaEnVideos = contenedor.querySelector(`#categoria-${id}`);
            
            if (categoriaEnVideos && contenedor) {
                // Debug: ver qué categoría estamos buscando EN LA VISTA DE VIDEOS
                console.log('Buscando categoría ID en VIDEOS:', id);
                console.log('Contenedor de videos encontrado:', categoriaEnVideos);
                
                // Buscar productos con .snap-start dentro de la categoría EN LA VISTA DE VIDEOS
                const productosEnCategoria = categoriaEnVideos.querySelectorAll('.snap-start');
                console.log('Productos encontrados en categoría (vista videos):', productosEnCategoria.length);
                
                if (productosEnCategoria.length > 0) {
                    const primerProducto = productosEnCategoria[0];
                    console.log('Primer producto en videos:', primerProducto);
                    
                    // Obtener posición relativa al contenedor con scroll
                    const contenedorRect = contenedor.getBoundingClientRect();
                    const productoRect = primerProducto.getBoundingClientRect();
                    
                    // Calcular la posición de scroll necesaria
                    const scrollActual = contenedor.scrollTop;
                    const posicionProducto = productoRect.top - contenedorRect.top + scrollActual;
                    
                    console.log('Scroll actual:', scrollActual);
                    console.log('Posición calculada:', posicionProducto);
                    
                    // Hacer scroll directo a esa posición
                    contenedor.scrollTo({
                        top: posicionProducto,
                        behavior: 'smooth'
                    });
                } else {
                    console.log('No se encontraron productos con .snap-start en la categoría de videos');
                }
                
                this.categoriaActiva = id;
            } else {
                console.log('No se encontró la categoría en el contenedor de videos');
            }
        },
        onScroll() {
            const contenedor = document.getElementById('contenedorVideos');
            const offsetTop = contenedor.scrollTop;
            const height = contenedor.clientHeight;

            for (let el of this.categorias) {
                const boxTop = el.offsetTop;
                const boxHeight = el.offsetHeight;

                if (boxTop <= offsetTop + height / 2 && boxTop + boxHeight > offsetTop + height / 2) {
                    const id = el.getAttribute('id').replace('categoria-', '');
                    this.categoriaActiva = id;
                    break;
                }
            }
        }
    };
}
</script>


<script>
  function menuCarrito() {
    return {
        // --- UI y carrito ---
        carrito: [],
        mostrarCarrito: false,
        modalProducto: false,
        productoSeleccionado: null,
        mesa_id: null,
        mostrarGraciasModal: false,
        mostrarVideos: false,
        categorias: @json($categorias->pluck('id')),
        activeCategory: null, // 👈 para scrollspy

        init() {
            const params = new URLSearchParams(window.location.search);
            this.mesa_id = params.get('mesa_id');
            this.activeCategory = this.categorias[0];

            window.addEventListener('scroll', () => {
                for (let c of this.categorias) {
                    const el = document.getElementById('categoria-' + c);
                    if (el && el.getBoundingClientRect().top <= 100) {
                        this.activeCategory = c;
                    }
                }
            });
        },

        agregarAlCarrito(id, nombre, precio_base) {
            const existente = this.carrito.find(p =>
                p.id === id &&
                (!p.adiciones || p.adiciones.length === 0)
            );

            if (existente) {
                existente.cantidad++;
            } else {
                this.carrito.push({
                    id,
                    nombre,
                    precio_base: parseFloat(precio_base),
                    cantidad: 1,
                    adiciones: []
                });
            }
        },

        quitarDelCarrito(id, adiciones = []) {
            const index = this.carrito.findIndex(p =>
                p.id === id &&
                JSON.stringify(p.adiciones || []) === JSON.stringify(adiciones || [])
            );

            if (index !== -1) {
                if (this.carrito[index].cantidad > 1) {
                    this.carrito[index].cantidad--;
                } else {
                    this.carrito.splice(index, 1);
                }
            }
        },

        cantidadEnCarrito(id) {
            return this.carrito
                .filter(p => p.id === id)
                .reduce((acc, item) => acc + item.cantidad, 0);
        },

        abrirDetalle(producto) {
            this.productoSeleccionado = {
                ...producto,
                adiciones: []
            };
            this.modalProducto = true;
        },

        calcularPrecioTotal() {
            if (!this.productoSeleccionado) return 0;
            let base = parseFloat(this.productoSeleccionado.precio) || 0;
            let extras = (this.productoSeleccionado.adiciones || []).reduce((acc, a) => acc + parseFloat(a.precio || 0), 0);
            return base + extras;
        },

        agregarConAdiciones() {
            if (!this.productoSeleccionado) return;

            const {
                id,
                nombre,
                precio,
                adiciones
            } = this.productoSeleccionado;
            const itemExistente = this.carrito.find(p =>
                p.id === id &&
                JSON.stringify(p.adiciones || []) === JSON.stringify(adiciones || [])
            );

            if (itemExistente) {
                itemExistente.cantidad++;
            } else {
                this.carrito.push({
                    id,
                    nombre,
                    precio_base: parseFloat(precio),
                    cantidad: 1,
                    adiciones: [...(adiciones || [])]
                });
            }

            this.modalProducto = false;
        },

        get totalCantidad() {
            return this.carrito.reduce((acc, item) => acc + item.cantidad, 0);
        },

        get totalPrecio() {
            return this.carrito.reduce((acc, item) => {
                const precioBase = item.precio_base * item.cantidad;
                const adiciones = item.adiciones ?
                    item.adiciones.reduce((suma, a) => suma + parseFloat(a.precio || 0), 0) * item.cantidad :
                    0;
                return acc + precioBase + adiciones;
            }, 0);
        },

        enviarPedido() {
            if (!this.mesa_id) {
                alert('Error: No se ha identificado la mesa.');
                return;
            }

            if (this.carrito.length === 0) {
                alert('Tu carrito está vacío.');
                return;
            }

            fetch('{{ route("comandas.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        mesa_id: this.mesa_id,
                        carrito: this.carrito
                    })
                })
                .then(res => res.json())
                .then(data => {
                    this.carrito = [];
                    this.mostrarCarrito = false;

                    let ordenesNuevas = parseInt(localStorage.getItem('ordenesNuevas') || 0);
                    ordenesNuevas += 1;
                    localStorage.setItem('ordenesNuevas', ordenesNuevas);

                    const nav = document.querySelector('nav[x-data]');
                    if (nav && nav.__x) {
                        nav.__x.$data.ordenesNuevas = ordenesNuevas;
                    }

                    this.mostrarGraciasModal = true;
                })
                .catch(err => {
                    console.error(err);
                    alert('Ocurrió un error al procesar tu pedido.');
                });
        },

        redirigirPedido() {
            window.location.href = `/seguimiento?mesa_id=${this.mesa_id}`;
        }
    }
}
</script>


@endsection
