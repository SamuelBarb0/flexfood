@extends('layouts.app')

@section('title', 'Editar Serie de Facturación · ' . $restaurante->nombre)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">
                Editar Serie: {{ $serie->codigo_serie }}
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

    @if(session('ok'))
        <div class="mb-4 rounded-md bg-green-50 p-3 text-green-700 text-sm">
            {{ session('ok') }}
        </div>
    @endif

    <form action="{{ route('fiscal.serie.update', [$restaurante, $serie]) }}" method="POST" class="bg-white shadow-sm rounded-xl p-6 border">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            {{-- Información no editable --}}
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Información de la Serie</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div>
                        <span class="text-gray-500">Código:</span>
                        <span class="font-medium ml-2">{{ $serie->codigo_serie }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Último número:</span>
                        <span class="font-medium ml-2">{{ $serie->ultimo_numero }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Próximo número:</span>
                        <span class="font-medium ml-2">{{ $serie->previewSiguienteNumero() }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Tipo:</span>
                        <span class="font-medium ml-2">{{ ucfirst($serie->tipo) }}</span>
                    </div>
                </div>
            </div>

            {{-- Campos editables --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Información Editable</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="nombre"
                            value="{{ old('nombre', $serie->nombre) }}"
                            class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                            required
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <textarea
                            name="descripcion"
                            rows="2"
                            class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                        >{{ old('descripcion', $serie->descripcion) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Estado --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-4">Estado</h3>
                <div class="space-y-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            name="activa"
                            value="1"
                            {{ old('activa', $serie->activa) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-[#153958] focus:ring-0"
                        >
                        <span class="text-sm text-gray-700">
                            <strong>Serie activa</strong>
                            <span class="text-gray-500 block text-xs">Desactivar impide emitir nuevas facturas con esta serie</span>
                        </span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            name="es_principal"
                            value="1"
                            {{ old('es_principal', $serie->es_principal) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-[#153958] focus:ring-0"
                        >
                        <span class="text-sm text-gray-700">
                            <strong>Serie principal</strong>
                            <span class="text-gray-500 block text-xs">Solo puede haber una serie principal por restaurante</span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="text-xs text-yellow-800">
                    <strong>Nota:</strong> El código, prefijo, sufijo y configuración de numeración no se pueden modificar una vez creada la serie para mantener la integridad de las facturas emitidas.
                </p>
            </div>
        </div>

        <div class="mt-8 flex items-center justify-end gap-3 pt-6 border-t">
            <a href="{{ route('settings.edit', $restaurante) }}?tab=fiscal" class="text-sm text-gray-600 hover:text-gray-800">
                Cancelar
            </a>
            <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-[#153958] text-white text-sm hover:opacity-95">
                Guardar Cambios
            </button>
        </div>
    </form>
</div>
@endsection
