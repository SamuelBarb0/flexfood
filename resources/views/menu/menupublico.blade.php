@extends('layouts.app')

@section('title', 'Menú Público')

@section('content')

<style>
  /* evita desbordes del documento */
  html, body { overflow-x: hidden; -webkit-text-size-adjust: 100%; }

  /* rail del carrusel */
  .cat-rail{
    scroll-snap-type: x mandatory;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-x: contain;
    padding-inline: 16px;          /* margen lateral/gutter */
    scroll-padding-inline: 16px;    /* el snap respeta el gutter */
  }

  /* ocultar la barra sin desactivar swipe */
  .no-scrollbar::-webkit-scrollbar { display: none; }
  .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>



<div class="max-w-7xl mx-auto px-4 py-8 pb-24" x-data="menuCarrito()" x-init="init">

  {{-- Logo y título --}}
  <div class="text-center mb-4">
    <img src="{{ asset('images/flexfood.png') }}" alt="Logo FlexFood" class="mx-auto h-20 mb-2">
  </div>
  <h1 class="text-3xl font-bold text-[#0C3558] mb-6 text-center">
    Nuestro Menú @isset($restaurante) – {{ $restaurante->nombre }} @endisset
  </h1>

{{-- Carrusel de categorías: chips auto-ancho por contenido (sin min-width) --}}
<div class="sticky top-0 z-40 bg-white py-3 mb-6 border-b shadow-sm">
  <div class="no-scrollbar overflow-x-auto cat-rail w-full min-w-0">
    <div class="flex w-max min-w-0 gap-2">
      @foreach ($categorias as $categoria)
        <a href="#categoria-{{ $categoria->id }}"
           class="flex-none snap-start
                  inline-flex items-center justify-center
                  h-9 px-3 rounded-full
                  border border-[#0C3558]/20 bg-white text-[#0C3558]
                  text-sm font-semibold text-center shadow-sm
                  transition-colors duration-200 hover:bg-[#0C3558] hover:text-white
                  whitespace-nowrap truncate
                  max-w-[65vw] sm:max-w-[45vw] md:max-w-[32vw]"  {{-- límite para nombres largos --}}
           style="touch-action: pan-x;"
           title="{{ $categoria->nombre }}">
          <span class="truncate">{{ $categoria->nombre }}</span>
        </a>
      @endforeach
    </div>
  </div>
</div>


  {{-- Listado de productos por categoría --}}
  @foreach ($categorias as $categoria)
    @if ($categoria->productos->where('disponible', true)->count())
      <div class="mb-10 opacity-0 animate-fadeIn" id="categoria-{{ $categoria->id }}">
        <h2 class="text-2xl font-semibold text-[#3CB28B] mb-4">{{ $categoria->nombre }}</h2>

        <div class="flex flex-col gap-6">
          @foreach ($categoria->productos->where('disponible', true) as $producto)
            <div class="border border-gray-200 rounded-xl p-4 shadow-sm bg-white text-[#0C3558]
                        transition-transform duration-300 md:hover:scale-105 group overflow-hidden">
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

  {{-- Modales --}}
  @include('menu.partials.modal-detalle-producto')
  @include('menu.partials.modal-gracias')
  @include('menu.partials.modal-carrito')
  @include('menu.partials.vista-videos')

  {{-- Menú inferior fijo tipo app --}}
  <div class="fixed bottom-0 left-0 right-0 bg-[#0C3558] text-white flex justify-around items-center py-2 z-[100] border-t">
    <button type="button" @click="cerrarModales(); mostrarVideos = true" class="flex flex-col items-center text-sm focus:outline-none" aria-label="Abrir videos">
      <span class="text-lg">🎥</span><span>Video</span>
    </button>
    <button type="button" @click="cerrarModales(); $nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))" class="flex flex-col items-center text-sm focus:outline-none" aria-label="Volver al menú">
      <span class="text-lg">📋</span><span>Menú</span>
    </button>
    <button type="button" @click="cerrarModales(); mostrarCarrito = true" class="flex flex-col items-center text-sm focus:outline-none relative" aria-label="Abrir carrito">
      <div class="relative">
        <span class="text-lg">🛒</span>
        <span x-show="totalCantidad > 0" x-text="totalCantidad"
              class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold min-w-[20px]"></span>
      </div>
      <span>Mi pedido</span>
    </button>
  </div>

<script>
function menuCarrito() {
  const ENDPOINTS = {
    store: "{{ route('comandas.store', $restaurante) }}",
    seguimiento: "{{ route('seguimiento', $restaurante) }}",
  };

  return {
    carrito: [],
    mostrarCarrito: false,
    modalProducto: false,
    productoSeleccionado: null,
    mesa_id: null,
    mostrarGraciasModal: false,
    mostrarVideos: false,
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
      const existente = this.carrito.find(p => p.id === id && (!p.adiciones || p.adiciones.length === 0));
      if (existente) { existente.cantidad++; }
      else { this.carrito.push({ id, nombre, precio_base: parseFloat(precio_base), cantidad: 1, adiciones: [] }); }
    },

    quitarDelCarrito(id, adiciones = []) {
      const index = this.carrito.findIndex(p =>
        p.id === id && JSON.stringify(p.adiciones || []) === JSON.stringify(adiciones || [])
      );
      if (index !== -1) {
        if (this.carrito[index].cantidad > 1) this.carrito[index].cantidad--;
        else this.carrito.splice(index, 1);
      }
    },

    cantidadEnCarrito(id) {
      return this.carrito.filter(p => p.id === id).reduce((acc, item) => acc + item.cantidad, 0);
    },

    abrirDetalle(producto) { this.productoSeleccionado = { ...producto, adiciones: [] }; this.modalProducto = true; },

    calcularPrecioTotal() {
      if (!this.productoSeleccionado) return 0;
      let base = parseFloat(this.productoSeleccionado.precio) || 0;
      let extras = (this.productoSeleccionado.adiciones || []).reduce((acc, a) => acc + parseFloat(a.precio || 0), 0);
      return base + extras;
    },

    agregarConAdiciones() {
      if (!this.productoSeleccionado) return;
      const { id, nombre, precio, adiciones } = this.productoSeleccionado;
      const item = this.carrito.find(p => p.id===id && JSON.stringify(p.adiciones||[])===JSON.stringify(adiciones||[]));
      if (item) item.cantidad++;
      else this.carrito.push({ id, nombre, precio_base: parseFloat(precio), cantidad:1, adiciones:[...(adiciones||[])] });
      this.modalProducto = false;
    },

    get totalCantidad() { return this.carrito.reduce((a,i)=>a+i.cantidad,0); },

    get totalPrecio() {
      return this.carrito.reduce((acc,item)=>{
        const base=item.precio_base*item.cantidad;
        const adds=item.adiciones? item.adiciones.reduce((s,a)=>s+parseFloat(a.precio||0),0)*item.cantidad : 0;
        return acc+base+adds;
      },0);
    },

    enviarPedido() {
      if (!this.mesa_id) { alert('Error: No se ha identificado la mesa.'); return; }
      if (!this.carrito.length) { alert('Tu carrito está vacío.'); return; }

      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
      fetch(ENDPOINTS.store, {
        method:'POST',
        headers:{ 'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest' },
        credentials:'same-origin',
        body: JSON.stringify({ mesa_id:this.mesa_id, carrito:this.carrito })
      })
      .then(async res=>{
        const ct=res.headers.get('content-type')||'';
        if(!res.ok||!ct.includes('application/json')){ const txt=await res.text(); throw new Error(`HTTP ${res.status}: ${txt.slice(0,200)}`); }
        return res.json();
      })
      .then(()=>{
        this.carrito=[]; this.mostrarCarrito=false;
        let n=parseInt(localStorage.getItem('ordenesNuevas')||0)+1;
        localStorage.setItem('ordenesNuevas',n);
        const nav=document.querySelector('nav[x-data]'); if(nav&&nav.__x) nav.__x.$data.ordenesNuevas=n;
        this.mostrarGraciasModal=true;
      })
      .catch(err=>{ console.error(err); alert('Ocurrió un error al procesar tu pedido.'); });
    },

    incrementarLinea(item){
      const linea=this.carrito.find(p=>p.id===item.id && JSON.stringify(p.adiciones||[])===JSON.stringify(item.adiciones||[]));
      if(linea) linea.cantidad++;
    },

    get resumenPorProducto(){
      const map={}; for (const i of this.carrito) map[i.nombre]=(map[i.nombre]||0)+i.cantidad;
      return Object.entries(map).map(([nombre,cantidad])=>({nombre,cantidad}));
    },

    redirigirPedido(){
      const url=new URL(ENDPOINTS.seguimiento, window.location.origin);
      url.searchParams.set('mesa_id', this.mesa_id);
      window.location.href=url.toString();
    }
  }
}
</script>

</div>
@endsection
