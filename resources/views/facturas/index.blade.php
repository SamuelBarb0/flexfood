@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="font-semibold text-2xl text-gray-800">
                Facturas Electrónicas
            </h2>
            <p class="text-sm text-gray-600 mt-1">
                {{ $restaurante->nombre }}
            </p>
        </div>
    </div>

    {{-- Estadísticas --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600">Total Facturas</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">{{ $estadisticas['total'] }}</div>
        </div>

        <div class="bg-yellow-50 rounded-lg shadow p-6">
            <div class="text-sm text-yellow-700">Pendientes</div>
            <div class="text-2xl font-bold text-yellow-900 mt-1">{{ $estadisticas['pendientes'] }}</div>
        </div>

        <div class="bg-green-50 rounded-lg shadow p-6">
            <div class="text-sm text-green-700">Aceptadas</div>
            <div class="text-2xl font-bold text-green-900 mt-1">{{ $estadisticas['aceptadas'] }}</div>
        </div>

        <div class="bg-red-50 rounded-lg shadow p-6">
            <div class="text-sm text-red-700">Rechazadas</div>
            <div class="text-2xl font-bold text-red-900 mt-1">{{ $estadisticas['rechazadas'] }}</div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow mb-6 p-6">
        <form method="GET" action="{{ route('facturas.index', $restaurante) }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select name="estado" class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0">
                    <option value="">Todos</option>
                    <option value="borrador" {{ request('estado') === 'borrador' ? 'selected' : '' }}>Borrador</option>
                    <option value="emitida" {{ request('estado') === 'emitida' ? 'selected' : '' }}>Emitida</option>
                    <option value="enviada" {{ request('estado') === 'enviada' ? 'selected' : '' }}>Enviada</option>
                    <option value="anulada" {{ request('estado') === 'anulada' ? 'selected' : '' }}>Anulada</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estado AEAT</label>
                <select name="aeat_estado" class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0">
                    <option value="">Todos</option>
                    <option value="pendiente" {{ request('aeat_estado') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="aceptada" {{ request('aeat_estado') === 'aceptada' ? 'selected' : '' }}>Aceptada</option>
                    <option value="rechazada" {{ request('aeat_estado') === 'rechazada' ? 'selected' : '' }}>Rechazada</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
                <input type="date" name="desde" value="{{ request('desde') }}"
                    class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
                <input type="date" name="hasta" value="{{ request('hasta') }}"
                    class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0">
            </div>

            <div class="md:col-span-4 flex justify-end gap-2">
                <a href="{{ route('facturas.index', $restaurante) }}"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    Limpiar
                </a>
                <button type="submit"
                    class="px-4 py-2 bg-[#153958] text-white rounded-lg hover:opacity-95">
                    Filtrar
                </button>
            </div>
        </form>
    </div>

    {{-- Listado de Facturas --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Número
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Fecha
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Estado
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        AEAT
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Total
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Acciones
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($facturas as $factura)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $factura->numero_factura }}
                            </div>
                            @if($factura->orden_id)
                                <div class="text-xs text-gray-500">Pedido #{{ $factura->orden_id }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $factura->fecha_emision->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $estadoBadge = match($factura->estado) {
                                    'borrador' => 'bg-gray-100 text-gray-800',
                                    'emitida' => 'bg-blue-100 text-blue-800',
                                    'enviada' => 'bg-green-100 text-green-800',
                                    'anulada' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800',
                                };
                            @endphp
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $estadoBadge }}">
                                {{ ucfirst($factura->estado) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $aeatBadge = match($factura->aeat_estado) {
                                    'pendiente' => 'bg-yellow-100 text-yellow-800',
                                    'aceptada' => 'bg-green-100 text-green-800',
                                    'rechazada' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800',
                                };
                            @endphp
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $aeatBadge }}">
                                {{ ucfirst($factura->aeat_estado) }}
                            </span>
                            @if($factura->verifactu_error)
                                <div class="text-xs text-red-600 mt-1" title="{{ $factura->verifactu_error }}">
                                    ⚠️ Error
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 font-medium">
                            €{{ number_format($factura->total_factura, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('facturas.show', [$restaurante, $factura]) }}"
                                    class="text-blue-600 hover:text-blue-900"
                                    title="Ver detalle">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>

                                @if($factura->aeat_estado === 'pendiente' && $factura->estado === 'emitida')
                                    <form method="POST" action="{{ route('facturas.reenviar', [$restaurante, $factura]) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                            class="text-green-600 hover:text-green-900"
                                            title="Reenviar a VeriFacti"
                                            onclick="return confirm('¿Reenviar esta factura a VeriFacti?')">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            No hay facturas para mostrar
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Paginación --}}
        @if($facturas->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $facturas->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
