@extends('layouts.app')

@section('title', 'Menú Público')

@section('content')
@php
$settings = $restaurante->siteSetting ?? null;
$isLegacy = ($restaurante->plan ?? 'legacy') === 'legacy';
@endphp
@php
$esUsuarioRestaurante = auth()->check() && $restaurante->users->contains('id', auth()->id());
@endphp

{{-- Estilos críticos para móvil --}}
<style>
  /* Reset para prevenir overflow */
  * {
    box-sizing: border-box;
  }

  html,
  body {
    width: 100%;
    overflow-x: hidden;
    margin: 0;
    padding: 0;
    touch-action: pan-y;
    overscroll-behavior: contain;
  }

  /* Prevenir zoom en inputs iOS */
  input,
  textarea,
  select,
  button {
    font-size: 16px !important;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
  }

  /* Contenedor principal */
  .main-wrapper {
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
    position: relative;
  }

  /* Carrusel de categorías */
  .cat-nav-fixed {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    width: 100%;
    background: white;
    padding: 12px 16px;
    border-bottom: 1px solid #ddd;
    z-index: 10;
    overflow-x: auto;
    overflow-y: hidden;
    white-space: nowrap;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    -webkit-overflow-scrolling: touch;
    -ms-overflow-style: none;
    scrollbar-width: none;
    touch-action: pan-x;
    overscroll-behavior-x: contain;
  }

  .cat-nav-fixed::-webkit-scrollbar {
    display: none;
  }

  .cat-nav-link {
    display: inline-block;
    margin-right: 8px;
    padding: 8px 16px;
    background: #0C3558;
    color: white;
    border-radius: 25px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    white-space: nowrap;
    transition: background-color 0.3s ease;
    flex-shrink: 0;
    -webkit-tap-highlight-color: transparent;
  }

  .cat-nav-link:hover,
  .cat-nav-link:active {
    background-color: #3CB28B;
  }

  /* Contenido principal */
  .content-container {
    width: 100%;
    max-width: 100%;
    padding: 0 16px 80px 16px;
    touch-action: pan-y;
    -webkit-overflow-scrolling: touch;
  }

  @media (min-width: 768px) {
    .content-container {
      max-width: 896px;
      margin: 0 auto;
    }
  }

  /* Menu inferior */
  .bottom-menu {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    width: 100%;
    background: #0C3558;
    color: white;
    display: flex;
    justify-content: space-around;
    align-items: center;
    padding: 8px 0;
    z-index: 100;
    border-top: 1px solid #ddd;
  }

  .bottom-menu button {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    font-size: 14px;
    background: none;
    border: none;
    color: white;
    padding: 4px;
    -webkit-tap-highlight-color: transparent;
  }

  /* Productos */
  .product-card {
    width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
    background: white;
    transition: transform 0.3s;
  }

  .product-card:active {
    transform: scale(0.98);
  }

  .product-image {
    width: 100%;
    height: 160px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 12px;
  }

  /* Animaciones */
  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(10px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .animate-fadeIn {
    animation: fadeIn 0.5s ease forwards;
  }

  /* Prevenir scroll horizontal */
  .overflow-guard {
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
    touch-action: pan-y;
    overscroll-behavior: contain;
  }

  html {
    scroll-behavior: smooth;
  }

  [data-cat-section] {
    scroll-margin-top: 80px;
  }

  /* altura aprox del carrusel fijo */
</style>

@include('menu.partials.menu-carrito-js')

{{-- ROOT ALPINE --}}
<div class="main-wrapper" x-data="menuCarrito()" x-init="init">

  {{-- CARRUSEL POSITION FIXED --}}
  <div id="catNav" class="cat-nav-fixed" data-cat-nav>
    @foreach ($categorias as $categoria)
    @if ($categoria->productos->where('disponible', true)->count())
    <a href="#categoria-{{ $categoria->id }}"
      class="cat-nav-link"
      data-cat-link
      data-id="{{ $categoria->id }}">
      {{ $categoria->nombre }}
    </a>
    @endif
    @endforeach
  </div>

  {{-- Spacer para compensar el carrusel fixed --}}
  <div style="height: 70px;"></div>

  {{-- CONTENEDOR PRINCIPAL --}}
  <div class="content-container overflow-guard">
    {{-- Logo y título --}}
    <div class="text-center mb-4">
      @if(!empty($settings?->logo_path))
      <img
        src="{{ asset($settings->logo_path) }}"
        alt="{{ $settings->site_name ?? 'Logo' }}"
        class="mx-auto h-20 mb-2">
      @else
      <img
        src="{{ asset('images/flexfood.png') }}"
        alt="Logo FlexFood"
        class="mx-auto h-20 mb-2">
      @endif
    </div>

    <h1 class="text-2xl md:text-3xl font-bold text-[#0C3558] mb-6 text-center px-2">
      Nuestro Menú
    </h1>

    {{-- Productos por categoría --}}
    @foreach ($categorias as $categoria)
    @if ($categoria->productos->where('disponible', true)->count())
    <div class="mb-10 animate-fadeIn"
      id="categoria-{{ $categoria->id }}"
      data-cat-section
      data-id="{{ $categoria->id }}">

      <h2 class="text-xl md:text-2xl font-semibold text-[#3CB28B] mb-4 px-2">
        {{ $categoria->nombre }}
      </h2>

      <div class="flex flex-col gap-4">
        @foreach ($categoria->productos->where('disponible', true) as $producto)
        <div class="product-card">
          @if ($producto->imagen)
          <img src="{{ asset('images/' . $producto->imagen) }}"
            alt="{{ $producto->nombre }}"
            class="product-image">
          @endif

          <h3 class="text-lg font-bold uppercase text-[#0C3558]">
            {{ $producto->nombre }}
          </h3>

          <p class="text-sm text-gray-600 mb-2">
            {{ \Illuminate\Support\Str::limit($producto->descripcion, 60) }}
          </p>

          <p class="text-md font-semibold mb-3 text-[#0C3558]">
            €{{ number_format($producto->precio, 2) }}
          </p>

          <div class="flex justify-between items-center">
            <div class="opacity-0">🤍</div>
            <button
              type="button"
              x-on:click="abrirDetalle(@js([
      'id' => $producto->id,
      'nombre' => $producto->nombre,
      'descripcion' => $producto->descripcion,
      'precio' => (float) $producto->precio,
      'imagen' => $producto->imagen ? asset('images/'.$producto->imagen) : null,
      'adiciones_disponibles' => $producto->adiciones,
  ]))"
              class="bg-[#0C3558] hover:bg-[#3CB28B] transition-colors text-white font-bold rounded-full px-6 py-2 text-sm">
              Añadir
            </button>


          </div>
        </div>
        @endforeach
      </div>
    </div>
    @endif
    @endforeach

    {{-- Modales: videos solo si es legacy --}}
    @include('menu.partials.modal-detalle-producto')
    @include('menu.partials.modal-gracias')
    @include('menu.partials.modal-carrito')
    @if($isLegacy)
    @include('menu.partials.vista-videos')
    @endif

  </div>

  {{-- Menú inferior fijo --}}
  <div class="bottom-menu" id="menu-inferior">
    {{-- Video: solo legacy --}}
    @if($isLegacy)
    <button
      type="button"
      @click="cerrarModales(); mostrarVideos = true"
      aria-label="Abrir videos">
      <span class="text-lg">🎥</span>
      <span>Video</span>
    </button>
    @endif

    {{-- Menú --}}
    <button
      type="button"
      @click="cerrarModales(); $nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))"
      aria-label="Volver al menú">
      <span class="text-lg">📋</span>
      <span>Menú</span>
    </button>

    {{-- Mi pedido --}}
    <button
      type="button"
      @click="cerrarModales(); mostrarCarrito = true"
      class="relative"
      aria-label="Abrir carrito">
      <div class="relative">
        <span class="text-lg">🛒</span>
        <span
          x-show="totalCantidad > 0"
          x-text="totalCantidad"
          class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold">
        </span>
      </div>
      <span>Mi pedido</span>
    </button>
  </div>

  {{-- ===== Botón lateral (solo usuarios del restaurante) ===== --}}
  @if($esUsuarioRestaurante)
  <button
    type="button"
    @click="cerrarModales(); mostrarMesas = true"
    class="fixed right-3 md:right-6 bottom-24 md:bottom-32 z-[110] bg-[#0C3558] text-white rounded-full shadow-lg px-4 py-3 font-semibold"
    style="-webkit-tap-highlight-color: transparent;"
    aria-label="Abrir selector de mesas">
    🪑 Mesas
  </button>
  @endif

  {{-- ===== Drawer Mesas (solo usuarios del restaurante) ===== --}}
  @if($esUsuarioRestaurante)
  {{-- Backdrop --}}
  <div
    x-show="mostrarMesas"
    x-transition.opacity
    @click="mostrarMesas=false"
    class="fixed inset-0 bg-black/40 z-[119]"
    style="display:none"></div>

  {{-- Panel derecho --}}
  <aside
    x-show="mostrarMesas"
    x-transition:enter="transform transition ease-out duration-300"
    x-transition:enter-start="translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transform transition ease-in duration-200"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="translate-x-full"
    class="fixed top-0 right-0 h-full w-[92%] sm:w-[420px] bg-white z-[120] shadow-xl flex flex-col"
    style="display:none"
    aria-label="Selector de mesas">
    <div class="p-4 border-b flex items-center justify-between">
      <h3 class="text-lg font-bold text-[#0C3558]">Seleccionar mesa</h3>
      <button class="text-gray-600" @click="mostrarMesas=false" aria-label="Cerrar">✖</button>
    </div>

    <div class="p-4 space-y-3 overflow-y-auto">
      {{-- Resumen selección --}}
      <template x-if="mesaSeleccionada">
        <div class="p-3 rounded-lg border bg-gray-50">
          <div class="text-sm text-gray-600">Mesa seleccionada:</div>
          <div class="font-semibold" x-text="mesaSeleccionada?.nombre ?? ('Mesa #'+mesaSeleccionada?.id)"></div>
        </div>
      </template>

      {{-- Buscador simple --}}
      <input
        type="search"
        placeholder="Buscar mesa por nombre o número..."
        class="w-full border rounded-lg px-3 py-2"
        x-model="filtroMesa" />

      {{-- Listado de mesas --}}
      <div class="divide-y border rounded-lg">
        @forelse($mesas ?? [] as $mesa)
        <button
          type="button"
          class="w-full text-left p-3 hover:bg-gray-50 flex items-center justify-between"
          @click="setMesa({ id: {{ $mesa->id }}, nombre: @js($mesa->nombre ?? ('Mesa #'.$mesa->id)) })"
          x-show="coincideFiltro(@js($mesa->nombre ?? ('Mesa #'.$mesa->id)))">
          <div>
            <div class="font-semibold text-[#0C3558]">{{ $mesa->nombre ?? ('Mesa #'.$mesa->id) }}</div>
            @if(!empty($mesa->descripcion))
            <div class="text-xs text-gray-500">{{ $mesa->descripcion }}</div>
            @endif
          </div>
          <span x-show="mesaSeleccionada && mesaSeleccionada.id === {{ $mesa->id }}">✅</span>
        </button>
        @empty
        <div class="p-4 text-sm text-gray-500">No hay mesas configuradas.</div>
        @endforelse
      </div>
    </div>

    <div class="mt-auto p-4 border-t flex gap-3">
      <button
        type="button"
        class="flex-1 border rounded-lg px-4 py-2"
        @click="quitarMesa()">
        Quitar mesa
      </button>
      <button
        type="button"
        class="flex-1 bg-[#0C3558] hover:bg-[#3CB28B] text-white rounded-lg px-4 py-2 font-semibold"
        @click="confirmarMesa()">
        Usar esta mesa
      </button>
    </div>
  </aside>
  @endif

</div>

{{-- Script adicional para prevenir zoom por doble tap --}}
<script>
  // Prevenir zoom por doble tap en iOS
  document.addEventListener('gesturestart', function(e) {
    e.preventDefault();
  });

  // Solo bloquear pinch-zoom (multitouch), permitir scroll con un dedo
  let lastTouchEnd = 0;
  document.addEventListener('touchend', function(event) {
    const now = new Date().getTime();
    if (now - lastTouchEnd <= 300) {
      event.preventDefault();
    }
    lastTouchEnd = now;
  }, false);

  document.addEventListener('touchmove', function(event) {
    const isPinchZoom = (typeof event.scale === 'number' && event.scale !== 1) ||
      (event.touches && event.touches.length > 1);
    if (isPinchZoom) {
      event.preventDefault();
    }
  }, {
    passive: false
  });

  // Prevenir scroll rebote excesivo
  document.addEventListener('touchstart', function(e) {
    if (e.touches.length > 1) {
      e.preventDefault();
    }
  }, { passive: false });

  // Fix para viewport en iOS
  if (/iPhone|iPad|iPod/.test(navigator.userAgent)) {
    document.querySelector('meta[name="viewport"]').setAttribute('content',
      'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
  }

  // Mejorar comportamiento de scroll en contenedores
  document.addEventListener('DOMContentLoaded', function() {
    const contentContainer = document.querySelector('.content-container');
    if (contentContainer) {
      let isScrolling = false;
      contentContainer.addEventListener('touchstart', function() {
        isScrolling = true;
      });
      contentContainer.addEventListener('touchend', function() {
        isScrolling = false;
      });
    }
  });
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