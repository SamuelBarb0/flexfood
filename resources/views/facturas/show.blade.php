@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="font-semibold text-2xl text-gray-800">
                Factura {{ $factura->numero_factura }}
            </h2>
            <p class="text-sm text-gray-600 mt-1">
                {{ $restaurante->nombre }}
            </p>
        </div>
        <a href="{{ route('facturas.index', $restaurante) }}"
           class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
            Volver al listado
        </a>
    </div>

    {{-- Mensajes de éxito/error --}}
    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    {{-- Información Principal --}}
    <div class="bg-white rounded-lg shadow mb-6 p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Columna izquierda --}}
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Información de la Factura</h3>

                <div class="space-y-3">
                    <div>
                        <span class="text-sm text-gray-600">Número:</span>
                        <p class="font-medium">{{ $factura->numero_factura }}</p>
                    </div>

                    <div>
                        <span class="text-sm text-gray-600">Serie:</span>
                        <p class="font-medium">{{ $factura->serieFacturacion->nombre ?? 'N/A' }}</p>
                    </div>

                    <div>
                        <span class="text-sm text-gray-600">Fecha de Emisión:</span>
                        <p class="font-medium">{{ $factura->fecha_emision->format('d/m/Y H:i') }}</p>
                    </div>

                    @if($factura->orden_id)
                    <div>
                        <span class="text-sm text-gray-600">Pedido:</span>
                        <p class="font-medium">#{{ $factura->orden_id }}</p>
                    </div>
                    @endif

                    <div>
                        <span class="text-sm text-gray-600">Estado:</span>
                        <div class="mt-1">
                            @php
                                $estadoBadge = match($factura->estado) {
                                    'borrador' => 'bg-gray-100 text-gray-800',
                                    'emitida' => 'bg-blue-100 text-blue-800',
                                    'enviada' => 'bg-green-100 text-green-800',
                                    'anulada' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800',
                                };
                            @endphp
                            <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full {{ $estadoBadge }}">
                                {{ ucfirst($factura->estado) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Columna derecha --}}
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Estado VeriFacti</h3>

                <div class="space-y-3">
                    <div>
                        <span class="text-sm text-gray-600">Estado AEAT:</span>
                        <div class="mt-1">
                            @php
                                $aeatBadge = match($factura->aeat_estado) {
                                    'pendiente' => 'bg-yellow-100 text-yellow-800',
                                    'aceptada' => 'bg-green-100 text-green-800',
                                    'rechazada' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800',
                                };
                            @endphp
                            <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full {{ $aeatBadge }}">
                                {{ ucfirst($factura->aeat_estado) }}
                            </span>
                        </div>
                    </div>

                    @if($factura->verifactu_id)
                    <div>
                        <span class="text-sm text-gray-600">UUID VeriFacti:</span>
                        <p class="font-mono text-xs break-all mt-1">{{ $factura->verifactu_id }}</p>
                    </div>
                    @endif

                    @if($factura->verifactu_huella)
                    <div>
                        <span class="text-sm text-gray-600">Huella Digital:</span>
                        <p class="font-mono text-xs break-all mt-1">{{ $factura->verifactu_huella }}</p>
                    </div>
                    @endif

                    @if($factura->verifactu_error)
                    <div>
                        <span class="text-sm text-red-600">Error:</span>
                        <p class="text-sm text-red-700 mt-1">{{ $factura->verifactu_error }}</p>
                    </div>
                    @endif

                    @if($factura->qr_url)
                    <div>
                        <span class="text-sm text-gray-600">QR AEAT:</span>
                        <div class="mt-2">
                            <img src="{{ $factura->qr_url }}" alt="QR AEAT" class="w-32 h-32 border rounded">
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Líneas de Factura --}}
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Detalle de la Factura</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Concepto
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cantidad
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Precio Unit.
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Base Imponible
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            IVA
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($factura->lineas as $linea)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $linea->descripcion }}
                                </div>
                                @if($linea->adiciones && count($linea->adiciones) > 0)
                                    <div class="text-xs text-gray-500 mt-1">
                                        @foreach($linea->adiciones as $adicion)
                                            + {{ $adicion['nombre'] ?? '' }}
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-900">
                                {{ $linea->cantidad }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-gray-900">
                                €{{ number_format($linea->precio_unitario, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-gray-900">
                                €{{ number_format($linea->base_imponible, 2) }}
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-900">
                                {{ $linea->tipo_iva }}%
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">
                                €{{ number_format($linea->total_linea, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Totales --}}
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <div class="flex justify-end">
                <div class="w-64 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Base Imponible:</span>
                        <span class="font-medium">€{{ number_format($factura->base_imponible, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">IVA ({{ $factura->lineas->first()->tipo_iva ?? 10 }}%):</span>
                        <span class="font-medium">€{{ number_format($factura->cuota_iva, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold border-t pt-2">
                        <span>Total:</span>
                        <span>€{{ number_format($factura->total_factura, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Acciones --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Acciones</h3>

        <div class="flex flex-wrap gap-3">
            {{-- Descargar PDF --}}
            <a href="{{ route('facturas.pdf', [$restaurante, $factura]) }}"
               target="_blank"
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Descargar PDF
            </a>

            {{-- Reenviar a VeriFacti (solo si está pendiente o rechazada) --}}
            @if(in_array($factura->aeat_estado, ['pendiente', 'rechazada']) && $factura->estado === 'emitida')
                <form method="POST" action="{{ route('facturas.reenviar', [$restaurante, $factura]) }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center gap-2"
                            onclick="return confirm('¿Reenviar esta factura a VeriFacti?')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Reenviar a VeriFacti
                    </button>
                </form>
            @endif

            {{-- Anular factura (solo si es borrador) --}}
            @if($factura->estado === 'borrador')
                <form method="POST" action="{{ route('facturas.anular', [$restaurante, $factura]) }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center gap-2"
                            onclick="return confirm('¿Anular esta factura? Esta acción no se puede deshacer.')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Anular Factura
                    </button>
                </form>
            @endif
        </div>
    </div>

</div>
@endsection
