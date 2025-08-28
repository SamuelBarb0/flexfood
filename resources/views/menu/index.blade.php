@extends('layouts.app')
@section('title', 'Gestor de Menú')

@section('content')
@php
    // Variables que vienen del controlador (con fallback por si faltan)
    $soloFotos          = $soloFotos          ?? in_array($restaurante->plan ?? 'legacy', ['basic', 'advanced']);
    $maxProductos       = $maxProductos       ?? ( ($restaurante->plan ?? 'legacy') === 'basic' ? 50 : null );
    $productosActuales  = $productosActuales  ?? $categorias->pluck('productos')->flatten()->count();
    $limitFull          = !is_null($maxProductos) && $productosActuales >= $maxProductos;
@endphp

<div
  class="container mx-auto px-4 py-6"
  x-data="{
    openCategoria: false,
    editCategoriaId: null,
    openProducto: false,
    editProductoId: null,
    productoEditado: null,
    adicionesDisponibles: [],
    routeEditBase: @js(route('productos.update', [$restaurante, '__ID__'])),
    seleccionarCategoria(id) {
      if (this.productoEditado) this.productoEditado.categoria_id = id;
      this.adicionesDisponibles = (window.adicionesPorCategoria[id] || []);
    }
  }"
>
    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="space-y-2">
            <h1 class="text-2xl font-bold text-[#153958] text-center sm:text-left">Gestor de Menú</h1>

            <!-- Banner plan -->
            <div class="flex flex-wrap items-center gap-2">
                @if(!is_null($maxProductos))
                    <span class="text-sm px-3 py-1 rounded border bg-yellow-50 border-yellow-200 text-yellow-800">
                        Platos: <strong>{{ $productosActuales }} / {{ $maxProductos }}</strong>
                    </span>
                @else
                    <span class="text-sm px-3 py-1 rounded border bg-slate-50 border-slate-200 text-slate-700">
                        Platos: <strong>{{ $productosActuales }}</strong> (ilimitado)
                    </span>
                @endif

                @if($soloFotos)
                    <span class="text-xs px-2 py-1 rounded bg-blue-50 border border-blue-200 text-blue-700">
                        Este plan permite <b>solo productos con imagen</b> (sin video)
                    </span>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap justify-center sm:justify-end gap-2">
            <button @click="openCategoria = true" class="bg-[#153958] text-white px-4 py-2 rounded-md w-full sm:w-auto">
                + Nueva Categoría
            </button>

            <button
                @click="openProducto = true; productoEditado = null;"
                @disabled($limitFull)
                title="{{ $limitFull ? 'Has alcanzado el límite de platos de tu plan' : '' }}"
                class="px-4 py-2 rounded-md w-full sm:w-auto transition {{ $limitFull
                    ? 'bg-gray-300 text-gray-600 cursor-not-allowed'
                    : 'bg-green-600 text-white hover:bg-green-700' }}">
                + Nuevo Producto
            </button>

            <a href="{{ route('adiciones.index', $restaurante) }}"
               class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 w-full sm:w-auto">
                ➕ Gestionar Adiciones
            </a>

            <a href="{{ route('menu.publico', $restaurante) }}" target="_blank"
               class="bg-[#3CB28B] text-white px-4 py-2 rounded-md hover:bg-[#32a37e] w-full sm:w-auto">
                🌐 Ver Menú Público
            </a>
        </div>
    </div>

    <!-- Listado de Categorías y Productos -->
    <div class="space-y-6">
        @foreach ($categorias as $categoria)
        <div class="border p-4 rounded-md bg-white shadow-sm">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4 gap-2">
                <h2 class="text-xl font-semibold text-[#153958]">{{ $categoria->nombre }}</h2>
                <div class="space-x-2 text-sm flex justify-center sm:justify-end">
                    <button @click="editCategoriaId = {{ $categoria->id }}" class="text-blue-600 hover:underline">Editar</button>
                    <form action="{{ route('categorias.destroy', [$restaurante, $categoria->id]) }}" method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button class="text-red-600 hover:underline">Eliminar</button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($categoria->productos as $producto)
                <div class="border p-4 rounded bg-[#f9f9f9] flex gap-4 items-start">
                    @if ($producto->imagen)
                        <img src="{{ asset('images/' . $producto->imagen) }}" alt="Imagen {{ $producto->nombre }}"
                             class="w-16 h-16 object-cover rounded shadow-sm flex-shrink-0">
                    @endif

                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="text-lg font-bold break-words">{{ $producto->nombre }}</h3>
                            <span class="text-[#153958] font-semibold whitespace-nowrap">€{{ number_format($producto->precio, 2) }}</span>
                        </div>

                        @if($producto->descripcion)
                            <p class="text-sm text-gray-600 break-words mt-0.5">{{ $producto->descripcion }}</p>
                        @endif

                        <p class="text-sm mt-1 {{ $producto->disponible ? 'text-green-600' : 'text-red-500' }}">
                            {{ $producto->disponible ? '✅ Disponible' : '❌ No disponible' }}
                        </p>

                        <div class="mt-3 flex flex-wrap gap-3 text-sm">
                            <button
                                @click="
                                    productoEditado = {
                                        id: {{ $producto->id }},
                                        nombre: @js($producto->nombre),
                                        descripcion: @js($producto->descripcion),
                                        precio: {{ $producto->precio }},
                                        categoria_id: {{ $producto->categoria_id }},
                                        disponible: {{ $producto->disponible ? 'true' : 'false' }},
                                        adiciones: @json($producto->adiciones->pluck('id'))
                                    };
                                    seleccionarCategoria({{ $producto->categoria_id }});
                                    editProductoId = {{ $producto->id }};
                                "
                                class="text-blue-600 hover:underline">
                                Editar
                            </button>

                            <form action="{{ route('productos.destroy', [$restaurante, $producto->id]) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:underline">Eliminar</button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    <!-- Modales -->
    @include('partials.modal-crear-categoria')      {{-- debe usar route('categorias.store', $restaurante) --}}
    @include('partials.modal-editar-categoria')     {{-- debe usar route('categorias.update', [$restaurante, $categoria]) --}}
    @include('partials.modal-crear-producto')       {{-- usa route('productos.store', $restaurante) y respeta $soloFotos --}}
    @include('partials.modal-editar-producto')      {{-- usa :action="routeEditBase.replace('__ID__', productoEditado.id)" y respeta $soloFotos --}}
</div>

{{-- Adiciones por categoría SOLO del restaurante actual, sin nuevo query --}}
@php
    $adicionesMap = $categorias->mapWithKeys(function ($cat) {
        return [
            $cat->id => $cat->adiciones->map(fn ($a) => [
                'id'     => $a->id,
                'nombre' => $a->nombre,
                'precio' => $a->precio ?? 0,
            ])->values(),
        ];
    });
@endphp
<script>
  window.adicionesPorCategoria = @json($adicionesMap);
</script>
@endsection
