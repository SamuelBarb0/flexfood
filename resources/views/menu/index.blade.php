@extends('layouts.app')

@section('title', 'Gestor de Menú')

@section('content')
<div
    class="container mx-auto px-4 py-6"
    x-data="{
        openCategoria: false,
        editCategoriaId: null,
        openProducto: false,
        editProductoId: null,
        productoEditado: null,
        adicionesDisponibles: [],
        seleccionarCategoria(id) {
            this.productoEditado.categoria_id = id;
            this.adicionesDisponibles = window.adicionesPorCategoria[id] || [];
        }
    }"
>

    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-[#153958] text-center sm:text-left">Gestor de Menú</h1>
        <div class="flex flex-wrap justify-center sm:justify-end gap-2">
            <button @click="openCategoria = true" class="bg-[#153958] text-white px-4 py-2 rounded-md w-full sm:w-auto">
                + Nueva Categoría
            </button>
            <button @click="openProducto = true; productoEditado = null;" class="bg-green-600 text-white px-4 py-2 rounded-md w-full sm:w-auto">
                + Nuevo Producto
            </button>
            <a href="{{ route('adiciones.index') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 w-full sm:w-auto">
                ➕ Gestionar Adiciones
            </a>
            <a href="{{ route('menu.publico') }}" target="_blank"
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
                    <form action="{{ route('categorias.destroy', $categoria->id) }}" method="POST" class="inline">
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
                        <h3 class="text-lg font-bold break-words">{{ $producto->nombre }}</h3>
                        <p class="text-sm text-gray-600 break-words">{{ $producto->descripcion }}</p>
                        <p class="text-[#153958] font-semibold mt-1">€{{ number_format($producto->precio, 0) }}</p>

                        <p class="text-sm mt-1 {{ $producto->disponible ? 'text-green-600' : 'text-red-500' }}">
                            {{ $producto->disponible ? '✅ Disponible' : '❌ No disponible' }}
                        </p>

                        <div class="mt-3 flex flex-wrap gap-3 text-sm">
                            <button
                                @click="
                                    editProductoId = {{ $producto->id }};
                                    productoEditado = {
                                        id: {{ $producto->id }},
                                        nombre: '{{ $producto->nombre }}',
                                        descripcion: `{{ $producto->descripcion }}`,
                                        precio: {{ $producto->precio }},
                                        categoria_id: {{ $producto->categoria_id }},
                                        disponible: {{ $producto->disponible ? 'true' : 'false' }},
                                        adiciones: @json($producto->adiciones->pluck('id'))
                                    };
                                    seleccionarCategoria({{ $producto->categoria_id }});
                                "
                                class="text-blue-600 hover:underline"
                            >Editar</button>
                            <form action="{{ route('productos.destroy', $producto->id) }}" method="POST" class="inline">
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
    @include('partials.modal-crear-categoria')
    @include('partials.modal-editar-categoria')
    @include('partials.modal-crear-producto')
    @include('partials.modal-editar-producto')

</div>

<script>
    window.adicionesPorCategoria = @json(
        \App\Models\Categoria::with(['adiciones' => function($q) {
            $q->select('adiciones.id', 'adiciones.nombre', 'adiciones.precio');
        }])->get()->mapWithKeys(fn ($cat) => [$cat->id => $cat->adiciones])
    );
</script>
@endsection
