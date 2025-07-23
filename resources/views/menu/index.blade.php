@extends('layouts.app')

@section('title', 'Gestor de Menú')



@section('content')
<div class="container mx-auto px-4 py-6" x-data="{ openCategoria: false, editCategoriaId: null, openProducto: false, editProductoId: null }">

    <!-- Encabezado -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-[#153958]">Gestor de Menú</h1>
        <div class="space-x-2">
            <button @click="openCategoria = true" class="bg-[#153958] text-white px-4 py-2 rounded-md">+ Nueva Categoría</button>
            <button @click="openProducto = true" class="bg-green-600 text-white px-4 py-2 rounded-md">+ Nuevo Producto</button>
        </div>
    </div>

    <!-- Listado de Categorías y Productos -->
    <div class="space-y-6">
        @foreach ($categorias as $categoria)
        <div class="border p-4 rounded-md bg-white shadow-sm">
            <div class="flex justify-between items-center mb-2">
                <h2 class="text-xl font-semibold text-[#153958]">{{ $categoria->nombre }}</h2>
                <div class="space-x-2 text-sm">
                    <button @click="editCategoriaId = {{ $categoria->id }}" class="text-blue-600 hover:underline">Editar</button>
                    <form action="{{ route('categorias.destroy', $categoria->id) }}" method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button class="text-red-600 hover:underline">Eliminar</button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($categoria->productos as $producto)
                <div class="border p-4 rounded bg-[#f9f9f9]">
                    <h3 class="text-lg font-bold">{{ $producto->nombre }}</h3>
                    <p class="text-sm text-gray-600">{{ $producto->descripcion }}</p>
                    <p class="text-[#153958] font-semibold">${{ number_format($producto->precio, 0) }}</p>
                    <div class="mt-2 flex justify-end space-x-2 text-sm">
                        <button @click="editProductoId = {{ $producto->id }}" class="text-blue-600 hover:underline">Editar</button>
                        <form action="{{ route('productos.destroy', $producto->id) }}" method="POST" class="inline">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:underline">Eliminar</button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    <!-- Modal: Crear Categoría -->
    <div
    x-show="openCategoria"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center"
>
        <div class="bg-white rounded-lg p-6 w-full max-w-md" @click.away="openCategoria = false">
            <h2 class="text-lg font-semibold mb-4">Crear Categoría</h2>
            <form action="{{ route('categorias.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Nombre</label>
                    <input type="text" name="nombre" class="mt-1 block w-full border rounded px-3 py-2" required>
                </div>
                <div class="flex justify-end">
                    <button type="button" @click="openCategoria = false" class="mr-2 text-gray-600">Cancelar</button>
                    <button type="submit" class="bg-[#153958] text-white px-4 py-2 rounded">Guardar</button>
                </div>
            </form>
        </div>
    </div>


    <!-- 🔵 Modal: Editar Categoría (placeholder, puede usarse con JS/AJAX o redirigir a vista clásica) -->
    @foreach ($categorias as $categoria)
    <div
    x-show="editCategoriaId === {{ $categoria->id }}"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center"
>
        <div class="bg-white rounded-lg p-6 w-full max-w-md" @click.away="editCategoriaId = null">
            <h2 class="text-lg font-semibold mb-4">Editar Categoría</h2>
            <form action="{{ route('categorias.update', $categoria->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Nombre</label>
                    <input type="text" name="nombre" value="{{ $categoria->nombre }}" class="mt-1 block w-full border rounded px-3 py-2" required>
                </div>
                <div class="flex justify-end">
                    <button type="button" @click="editCategoriaId = null" class="mr-2 text-gray-600">Cancelar</button>
                    <button type="submit" class="bg-[#153958] text-white px-4 py-2 rounded">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach


    <!-- 🟢 Modal: Crear Producto -->
   <div
    x-show="openProducto"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center"
>
        <div class="bg-white rounded-lg p-6 w-full max-w-md" @click.away="openProducto = false">
            <h2 class="text-lg font-semibold mb-4">Crear Producto</h2>
            <form action="{{ route('productos.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700">Nombre</label>
                    <input type="text" name="nombre" class="w-full border rounded px-3 py-2" required>
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700">Descripción</label>
                    <textarea name="descripcion" class="w-full border rounded px-3 py-2"></textarea>
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700">Precio</label>
                    <input type="number" step="0.01" name="precio" class="w-full border rounded px-3 py-2" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Categoría</label>
                    <select name="categoria_id" class="w-full border rounded px-3 py-2" required>
                        @foreach ($categorias as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end">
                    <button type="button" @click="openProducto = false" class="mr-2 text-gray-600">Cancelar</button>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Guardar</button>
                </div>
            </form>
        </div>
    </div>


    <!-- Modal: Editar Producto -->
    @foreach ($categorias as $categoria)
    @foreach ($categoria->productos as $producto)
    <div
    x-show="editProductoId === {{ $producto->id }}"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center"
>
        <div class="bg-white rounded-lg p-6 w-full max-w-md" @click.away="editProductoId = null">
            <h2 class="text-lg font-semibold mb-4">Editar Producto</h2>
            <form action="{{ route('productos.update', $producto->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700">Nombre</label>
                    <input type="text" name="nombre" value="{{ $producto->nombre }}" class="w-full border rounded px-3 py-2" required>
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700">Descripción</label>
                    <textarea name="descripcion" class="w-full border rounded px-3 py-2">{{ $producto->descripcion }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700">Precio</label>
                    <input type="number" step="0.01" name="precio" value="{{ $producto->precio }}" class="w-full border rounded px-3 py-2" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Categoría</label>
                    <select name="categoria_id" class="w-full border rounded px-3 py-2" required>
                        @foreach ($categorias as $cat)
                        <option value="{{ $cat->id }}" {{ $producto->categoria_id == $cat->id ? 'selected' : '' }}>
                            {{ $cat->nombre }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end">
                    <button type="button" @click="editProductoId = null" class="mr-2 text-gray-600">Cancelar</button>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach
    @endforeach


</div>
@endsection