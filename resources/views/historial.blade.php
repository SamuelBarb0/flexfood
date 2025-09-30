@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center sm:text-left">Historial de Pedidos por Mesa</h2>

    {{-- Tabla en pantallas grandes --}}
    <div class="bg-white shadow rounded-lg overflow-hidden hidden sm:block">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm text-left">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="px-4 py-3">Mesa</th>
                        <th class="px-4 py-3">Inicio</th>
                        <th class="px-4 py-3">Cierre</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Detalles</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($ordenes as $orden)
                        <tr>
                            <td class="px-4 py-3 font-semibold whitespace-nowrap">
                                {{ $orden->mesa->nombre ?? 'N/A' }}
                                @if($orden->mesa_anterior_id)
                                    <div class="text-xs text-orange-600 mt-1" title="Traspasada desde Mesa {{ $orden->mesaAnterior->nombre ?? $orden->mesa_anterior_id }}">
                                        🔄 Traspasada desde {{ $orden->mesaAnterior->nombre ?? 'Mesa ' . $orden->mesa_anterior_id }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $orden->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $orden->updated_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">{{ $estados[$orden->estado] ?? 'Desconocido' }}</td>
                            <td class="px-4 py-3 font-bold whitespace-nowrap">{{ number_format($orden->total, 2) }} €</td>
                            <td class="px-4 py-3">
                                <button onclick="document.getElementById('productos-lg-{{ $orden->id }}').classList.toggle('hidden')" class="text-blue-600 hover:underline">
                                    Ver productos
                                </button>
                            </td>
                        </tr>
                        <tr id="productos-lg-{{ $orden->id }}" class="hidden bg-gray-50">
                            <td colspan="6" class="px-6 py-4">
                                @include('partials._productos-orden', ['orden' => $orden])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-gray-500 py-4">No hay pedidos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Cards en móviles --}}
    <div class="space-y-4 sm:hidden">
        @forelse($ordenes as $orden)
            <div class="bg-white shadow rounded-lg p-4">
                <div class="flex justify-between items-center mb-2">
                    <div>
                        <h3 class="font-bold text-gray-800">Mesa: {{ $orden->mesa->nombre ?? 'N/A' }}</h3>
                        @if($orden->mesa_anterior_id)
                            <p class="text-xs text-orange-600 mt-1">
                                🔄 Traspasada desde {{ $orden->mesaAnterior->nombre ?? 'Mesa ' . $orden->mesa_anterior_id }}
                            </p>
                        @endif
                    </div>
                    <span class="text-xs bg-gray-200 text-gray-700 px-2 py-0.5 rounded">
                        {{ $estados[$orden->estado] ?? 'Desconocido' }}
                    </span>
                </div>
                <p class="text-sm text-gray-600 mb-1">Inicio: {{ $orden->created_at->format('d/m/Y H:i') }}</p>
                <p class="text-sm text-gray-600 mb-1">Cierre: {{ $orden->updated_at->format('d/m/Y H:i') }}</p>
                <p class="text-sm font-semibold text-[#153958]">Total: {{ number_format($orden->total, 2) }} €</p>

                <button onclick="document.getElementById('productos-m-{{ $orden->id }}').classList.toggle('hidden')"
                        class="text-blue-600 text-sm mt-2 hover:underline">
                    Ver productos
                </button>

                <div id="productos-m-{{ $orden->id }}" class="hidden mt-3 bg-gray-50 p-3 rounded">
                    @include('partials._productos-orden', ['orden' => $orden])
                </div>
            </div>
        @empty
            <p class="text-center text-gray-500">No hay pedidos registrados.</p>
        @endforelse
    </div>
</div>
@endsection
