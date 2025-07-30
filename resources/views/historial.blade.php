@extends('layouts.app')

@section('content')
<div class="px-6 py-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Historial de Pedidos por Mesa</h2>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="px-4 py-2 text-left">Mesa</th>
                    <th class="px-4 py-2">Inicio</th>
                    <th class="px-4 py-2">Cierre</th>
                    <th class="px-4 py-2">Estado</th>
                    <th class="px-4 py-2">Total</th>
                    <th class="px-4 py-2">Detalles</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ordenes as $orden)
                <tr class="border-b">
                    <td class="px-4 py-2 font-semibold">{{ $orden->mesa->nombre ?? 'N/A' }}</td>
                    <td class="px-4 py-2">{{ $orden->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-2">{{ $orden->updated_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $estados[$orden->estado] ?? 'Desconocido' }}</td>
                    <td class="px-4 py-2 font-bold">{{ number_format($orden->total, 2) }} €</td>
                    <td class="px-4 py-2">
                        <button onclick="document.getElementById('productos-{{ $orden->id }}').classList.toggle('hidden')" class="text-blue-600 hover:underline">
                            Ver productos
                        </button>
                    </td>
                </tr>
                <tr id="productos-{{ $orden->id }}" class="hidden bg-gray-50">
                    <td colspan="6" class="p-4">
                        @if(is_array($orden->productos))
                        <ul class="list-disc ml-5 text-gray-700">
                            @foreach($orden->productos as $producto)
                            <li>
                                <strong>{{ $producto['nombre'] }}</strong> - {{ $producto['cantidad'] }} uds. -
                                {{ number_format($producto['precio_base'], 2) }} €

                                @if(!empty($producto['adiciones']))
                                <ul class="ml-4 list-square text-sm text-gray-500">
                                    @foreach($producto['adiciones'] as $adicion)
                                    <li>{{ $adicion['nombre'] }} (+{{ number_format((float) $adicion['precio'], 2) }} €)</li>
                                    @endforeach
                                </ul>
                                @endif
                            </li>
                            @endforeach
                        </ul>
                        @else
                        <p class="text-sm text-gray-500">Sin productos registrados.</p>
                        @endif
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
@endsection