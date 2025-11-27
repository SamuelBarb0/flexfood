@extends('layouts.app')

@section('title', 'Nueva Serie de Facturación · ' . $restaurante->nombre)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">
                Nueva Serie de Facturación
            </h1>
            <p class="text-sm text-gray-500">{{ $restaurante->nombre }}</p>
        </div>
        <a href="{{ route('settings.edit', $restaurante) }}?tab=fiscal" class="text-sm text-gray-600 hover:text-gray-800">
            ← Volver a configuración
        </a>
    </div>

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 p-3 text-red-700 text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('fiscal.serie.store', $restaurante) }}" method="POST" class="bg-white shadow-sm rounded-xl p-6 border">
        @csrf

        <div class="space-y-6">
            {{-- Información básica --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Información Básica</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Código de Serie <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="codigo_serie"
                            value="{{ old('codigo_serie', 'FF-' . $anoActual) }}"
                            class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                            placeholder="Ej. FF-2025"
                            required
                        >
                        <p class="text-xs text-gray-500 mt-1">Identificador único de la serie. Ej: FF-2025, TPV-2025</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="nombre"
                            value="{{ old('nombre', 'Serie Principal ' . $anoActual) }}"
                            class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                            placeholder="Ej. Serie Principal 2025"
                            required
                        >
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <textarea
                            name="descripcion"
                            rows="2"
                            class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                            placeholder="Descripción opcional de la serie"
                        >{{ old('descripcion') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Configuración de numeración --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Configuración de Numeración</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prefijo</label>
                        <input
                            type="text"
                            name="prefijo"
                            value="{{ old('prefijo', 'FF') }}"
                            class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                            placeholder="Ej. FF"
                            maxlength="10"
                        >
                        <p class="text-xs text-gray-500 mt-1">Opcional</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sufijo</label>
                        <input
                            type="text"
                            name="sufijo"
                            value="{{ old('sufijo', $anoActual) }}"
                            class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                            placeholder="Ej. 2025"
                            maxlength="10"
                        >
                        <p class="text-xs text-gray-500 mt-1">Opcional</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Dígitos <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            name="digitos"
                            value="{{ old('digitos', 6) }}"
                            class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                            min="4"
                            max="10"
                            required
                        >
                        <p class="text-xs text-gray-500 mt-1">Ceros a la izquierda</p>
                    </div>
                </div>

                <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                    <p class="text-xs text-blue-800">
                        <strong>Ejemplo:</strong> Con prefijo "FF", sufijo "2025" y 6 dígitos, el número de factura será: <strong>FF-000001-2025</strong>
                    </p>
                </div>
            </div>

            {{-- Tipo y punto de venta --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Tipo y Punto de Venta</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tipo de Serie <span class="text-red-500">*</span>
                        </label>
                        <select
                            name="tipo"
                            class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                            required
                        >
                            <option value="principal" {{ old('tipo') === 'principal' ? 'selected' : '' }}>Principal</option>
                            <option value="secundaria" {{ old('tipo') === 'secundaria' ? 'selected' : '' }}>Secundaria</option>
                            <option value="rectificativa" {{ old('tipo') === 'rectificativa' ? 'selected' : '' }}>Rectificativa</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Punto de Venta <span class="text-red-500">*</span>
                        </label>
                        <select
                            name="punto_venta"
                            class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                            required
                        >
                            <option value="general" {{ old('punto_venta') === 'general' ? 'selected' : '' }}>General</option>
                            <option value="tpv" {{ old('punto_venta') === 'tpv' ? 'selected' : '' }}>TPV</option>
                            <option value="online" {{ old('punto_venta') === 'online' ? 'selected' : '' }}>Online</option>
                            <option value="delivery" {{ old('punto_venta') === 'delivery' ? 'selected' : '' }}>Delivery</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Año Fiscal</label>
                        <input
                            type="number"
                            name="ano_fiscal"
                            value="{{ old('ano_fiscal', $anoActual) }}"
                            class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                            min="2020"
                            max="2100"
                        >
                        <p class="text-xs text-gray-500 mt-1">Opcional</p>
                    </div>
                </div>
            </div>

            {{-- Serie principal --}}
            <div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        name="es_principal"
                        value="1"
                        {{ old('es_principal', true) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-[#153958] focus:ring-0"
                    >
                    <span class="text-sm text-gray-700">
                        <strong>Marcar como serie principal</strong>
                        <span class="text-gray-500 block text-xs">Solo puede haber una serie principal por restaurante</span>
                    </span>
                </label>
            </div>
        </div>

        <div class="mt-8 flex items-center justify-end gap-3 pt-6 border-t">
            <a href="{{ route('settings.edit', $restaurante) }}?tab=fiscal" class="text-sm text-gray-600 hover:text-gray-800">
                Cancelar
            </a>
            <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-[#153958] text-white text-sm hover:opacity-95">
                Crear Serie
            </button>
        </div>
    </form>
</div>
@endsection
