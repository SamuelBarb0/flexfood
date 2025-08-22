@extends('layouts.app')

@section('title', 'Menú Público')

@section('content')

{{-- ======================= ROOT ALPINE: engloba TODO ======================= --}}
<div x-data="menuCarrito()" x-init="init">

  {{-- CARRUSEL POSITION FIXED --}}
  <div id="catNav" data-cat-nav
      style="position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            width: 100vw !important;
            max-width: 100vw !important;
            background: white !important;
            padding: 12px 16px !important;
            border-bottom: 1px solid #ddd !important;
            z-index: 1;
            overflow-x: auto !important;
            overflow-y: hidden !important;
            white-space: nowrap !important;
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;">
    @foreach ($categorias as $categoria)
      @if ($categoria->productos->where('disponible', true)->count())
        <a href="#categoria-{{ $categoria->id }}"
          data-cat-link data-id="{{ $categoria->id }}"
          style="display: inline-block !important;
                margin-right: 8px !important;
                padding: 8px 16px !important;
                background: #0C3558 !important;
                color: white !important;
                border-radius: 25px !important;
                text-decoration: none !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                white-space: nowrap !important;
                transition: background-color 0.3s ease !important;
                flex-shrink: 0 !important;"
          onmouseover="this.style.backgroundColor='#3CB28B'"
          onmouseout="if(!this.dataset.active){ this.style.backgroundColor='#0C3558' }"
          onclick="event.preventDefault();">
          {{ $categoria->nombre }}
        </a>
      @endif
    @endforeach
  </div>

  {{-- Spacer dinámico para compensar el carrusel fixed --}}
  <div id="catSpacer" style="height: 70px !important;"></div>

  {{-- CONTENEDOR PRINCIPAL CON CONTENIDO --}}
  <div class="max-w-7xl mx-auto px-4 pb-8">

      {{-- Logo y título --}}
      <div class="text-center mb-4">
          <img src="{{ asset('images/flexfood.png') }}" alt="Logo FlexFood" class="mx-auto h-20 mb-2">
      </div>
      <h1 class="text-3xl font-bold text-[#0C3558] mb-6 text-center">
          Nuestro Menú @isset($restaurante) – {{ $restaurante->nombre }} @endisset
      </h1>

      {{-- Listado de productos por categoría --}}
      @foreach ($categorias as $categoria)
          @if ($categoria->productos->where('disponible', true)->count())
              <div class="mb-10 opacity-0 animate-fadeIn"
                  id="categoria-{{ $categoria->id }}"
                  data-cat-section data-id="{{ $categoria->id }}">
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
                                  <div class="text-[#0C3558] text-xl opacity-0 pointer-events-none">🤍</div>
                                  <button
                                      @click='abrirDetalle(JSON.parse(`{!! json_encode([
                                          "id" => $producto->id,
                                          "nombre" => $producto->nombre,
                                          "descripcion" => $producto->descripcion,
                                          "precio" => (float) $producto->precio,
                                          "imagen" => $producto->imagen ? asset("images/" . $producto->imagen) : null,
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

      {{-- Modales e includes --}}
      @include('menu.partials.modal-detalle-producto')
      @include('menu.partials.modal-gracias') 
      @include('menu.partials.modal-carrito')
      @include('menu.partials.vista-videos')

  </div>

  {{-- Menú inferior fijo tipo app --}}
  <div class="fixed bottom-0 left-0 right-0 bg-[#0C3558] text-white flex justify-around items-center py-2 z-[100] border-t" 
       id="menu-inferior">
    <!-- Video -->
    <button
      type="button"
      @click="cerrarModales(); mostrarVideos = true"
      class="flex flex-col items-center text-sm focus:outline-none"
      aria-label="Abrir videos">
      <span class="text-lg">🎥</span>
      <span>Video</span>
    </button>

    <!-- Menú -->
    <button
      type="button"
      @click="cerrarModales(); $nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))"
      class="flex flex-col items-center text-sm focus:outline-none"
      aria-label="Volver al menú">
      <span class="text-lg">📋</span>
      <span>Menú</span>
    </button>

    <!-- Mi pedido -->
    <button
      type="button"
      @click="cerrarModales(); mostrarCarrito = true"
      class="flex flex-col items-center text-sm focus:outline-none relative"
      aria-label="Abrir carrito">
      <div class="relative">
        <span class="text-lg">🛒</span>
        <span
          x-show="totalCantidad > 0"
          x-text="totalCantidad"
          class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold min-w-[20px]">
        </span>
      </div>
      <span>Mi pedido</span>
    </button>
  </div>

</div> {{-- /x-data root --}}

<script>
function menuCarrito() {
  const ENDPOINTS = {
    store: "{{ route('comandas.store', $restaurante) }}",
    seguimiento: "{{ route('seguimiento', $restaurante) }}",
  };

  return {
    // --- UI y carrito ---
    carrito: [],
    mostrarCarrito: false,
    modalProducto: false,
    productoSeleccionado: null,
    mesa_id: null,
    mostrarGraciasModal: false,
    mostrarVideos: false, // ← IMPORTANTE: inicia como FALSE
    categorias: @json($categorias->pluck('id')),
    activeCategory: null,

    cerrarModales() {
      this.mostrarCarrito = false;
      this.modalProducto = false;
      this.mostrarGraciasModal = false;
      this.mostrarVideos = false;
      this.productoSeleccionado = null;
    },

    init() {
      const params = new URLSearchParams(window.location.search);
      this.mesa_id = params.get('mesa_id');
      this.activeCategory = this.categorias[0];

      // FORZAR estado inicial de videos
      this.mostrarVideos = false;
      this.mostrarCarrito = false;
      this.modalProducto = false;
      this.mostrarGraciasModal = false;
    },

    abrirDetalle(producto) {
      this.productoSeleccionado = { ...producto, adiciones: [] };
      this.modalProducto = true;
    },

    calcularPrecioTotal() {
      if (!this.productoSeleccionado) return 0;
      let base = parseFloat(this.productoSeleccionado.precio) || 0;
      let extras = (this.productoSeleccionado.adiciones || [])
        .reduce((acc, a) => acc + parseFloat(a.precio || 0), 0);
      return base + extras;
    },

    agregarConAdiciones() {
      if (!this.productoSeleccionado) return;

      const { id, nombre, precio, adiciones } = this.productoSeleccionado;
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

    agregarAlCarrito(id, nombre, precio_base) {
      const existente = this.carrito.find(p =>
        p.id === id && (!p.adiciones || p.adiciones.length === 0)
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

    incrementarLinea(item) {
      const linea = this.carrito.find(p =>
        p.id === item.id &&
        JSON.stringify(p.adiciones || []) === JSON.stringify(item.adiciones || [])
      );
      if (linea) linea.cantidad++;
    },

    get resumenPorProducto() {
      const map = {};
      for (const i of this.carrito) {
        map[i.nombre] = (map[i.nombre] || 0) + i.cantidad;
      }
      return Object.entries(map).map(([nombre, cantidad]) => ({ nombre, cantidad }));
    },

    redirigirPedido() {
      const url = new URL(ENDPOINTS.seguimiento, window.location.origin);
      url.searchParams.set('mesa_id', this.mesa_id);
      window.location.href = url.toString();
    },

    get totalCantidad() {
      return this.carrito.reduce((acc, item) => acc + item.cantidad, 0);
    },

    get totalPrecio() {
      return this.carrito.reduce((acc, item) => {
        const precioBase = item.precio_base * item.cantidad;
        const adiciones = item.adiciones
          ? item.adiciones.reduce((suma, a) => suma + parseFloat(a.precio || 0), 0) * item.cantidad
          : 0;
        return acc + precioBase + adiciones;
      }, 0);
    },

    enviarPedido() {
      if (!this.mesa_id) { alert('Error: No se ha identificado la mesa.'); return; }
      if (!this.carrito.length) { alert('Tu carrito está vacío.'); return; }

      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

      fetch(ENDPOINTS.store, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          mesa_id: this.mesa_id,
          carrito: this.carrito
        })
      })
      .then(async (res) => {
        const ct = res.headers.get('content-type') || '';
        if (!res.ok || !ct.includes('application/json')) {
          const txt = await res.text();
          throw new Error(`HTTP ${res.status}: ${txt.slice(0,200)}`);
        }
        return res.json();
      })
      .then(() => {
        this.carrito = [];
        this.mostrarCarrito = false;
        this.mostrarGraciasModal = true;
      })
      .catch((err) => {
        console.error(err);
        alert('Ocurrió un error al procesar tu pedido.');
      });
    }
  }
}

// ScrollSpy para vista de videos
function scrollSpyCategorias() {
    return {
        categoriaActiva: null,
        categorias: [],
        botonesCarrusel: [],
        init() {
            this.categorias = [...document.querySelectorAll('#contenedorVideos [id^="categoria-"]')];
            this.botonesCarrusel = [...document.querySelectorAll('#contenedorVideos .overflow-x-auto a, #contenedorVideos .flex.justify-center a')];
            this.onScroll();
        },
        scrollToCategoria(id) {
            const contenedor = document.getElementById('contenedorVideos');
            const categoria = contenedor?.querySelector(`#categoria-${id}`);
            const producto = categoria?.querySelector('.snap-start');

            if (producto && contenedor) {
                const contenedorRect = contenedor.getBoundingClientRect();
                const productoRect = producto.getBoundingClientRect();
                const scrollActual = contenedor.scrollTop;
                const posicionProducto = productoRect.top - contenedorRect.top + scrollActual;

                contenedor.scrollTo({ top: posicionProducto, behavior: 'smooth' });
                this.categoriaActiva = id;
                this.scrollCarruselHorizontal(id);
            }
        },
        scrollCarruselHorizontal(id) {
            const categoriaIndex = this.categorias.findIndex(categoria => 
                categoria.getAttribute('id') === `categoria-${id}`
            );
            
            if (categoriaIndex === -1) return;

            const botonActivo = this.botonesCarrusel[categoriaIndex];
            if (!botonActivo) return;

            let carrusel = document.querySelector('#contenedorVideos .overflow-x-auto');
            
            if (!carrusel) {
                carrusel = botonActivo.closest('.overflow-x-auto');
            }

            if (!carrusel) {
                const contenedorCategorias = document.querySelector('#contenedorVideos .sticky.top-0');
                if (contenedorCategorias) {
                    carrusel = contenedorCategorias.querySelector('.overflow-x-auto');
                }
            }

            if (!carrusel) return;
            if (carrusel.scrollWidth <= carrusel.clientWidth) return;

            const carruselRect = carrusel.getBoundingClientRect();
            const botonRect = botonActivo.getBoundingClientRect();
            
            const scrollActual = carrusel.scrollLeft;
            const posicionBotonRelativa = botonRect.left - carruselRect.left + scrollActual;
            
            const mitadCarrusel = carrusel.clientWidth / 2;
            const mitadBoton = botonActivo.offsetWidth / 2;
            const scrollObjetivo = posicionBotonRelativa - mitadCarrusel + mitadBoton;
            
            const scrollMaximo = carrusel.scrollWidth - carrusel.clientWidth;
            const scrollFinal = Math.max(0, Math.min(scrollObjetivo, scrollMaximo));
            
            try {
                carrusel.scrollTo({ 
                    left: scrollFinal, 
                    behavior: 'smooth' 
                });
                
                setTimeout(() => {
                    if (carrusel.scrollLeft === scrollActual) {
                        carrusel.scrollLeft = scrollFinal;
                    }
                }, 100);
                
            } catch (error) {
                carrusel.scrollLeft = scrollFinal;
            }
        },
        onScroll() {
            const contenedor = document.getElementById('contenedorVideos');
            if (!contenedor) return;

            const scrollTop = contenedor.scrollTop;
            const containerHeight = contenedor.clientHeight;
            const puntoReferencia = scrollTop + (containerHeight * 0.5);

            let categoriaActual = null;

            for (let i = 0; i < this.categorias.length; i++) {
                const categoria = this.categorias[i];
                const siguienteCategoria = this.categorias[i + 1];
                const inicio = categoria.offsetTop;
                const fin = siguienteCategoria ? siguienteCategoria.offsetTop : inicio + categoria.offsetHeight;

                if (puntoReferencia >= inicio && puntoReferencia < fin) {
                    categoriaActual = categoria.getAttribute('id').replace('categoria-', '');
                    break;
                }
            }

            if (categoriaActual && categoriaActual !== this.categoriaActiva) {
                this.categoriaActiva = categoriaActual;
                this.scrollCarruselHorizontal(categoriaActual);
            }
        }
    };
}
</script>

<style>
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>

@endsection