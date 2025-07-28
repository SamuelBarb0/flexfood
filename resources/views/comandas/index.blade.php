@extends('layouts.app')

@section('content')
<div class="px-6 py-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Panel de Comandas</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Pendientes de Activación --}}
        <div>
            <h3 class="text-lg font-bold text-yellow-600 flex items-center mb-3">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M13 16h-1v-4h-1m1-4h.01M12 20h.01M21 12c0-4.97-4.03-9-9-9s-9 4.03-9 9
                     4.03 9 9 9 9-4.03 9-9z" />
                </svg>
                Pendientes de Activación
            </h3>

            @forelse($ordenesPendientes as $orden)
            <div class="bg-yellow-50 border-l-4 border-yellow-400 shadow-md rounded-md p-4 mb-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="text-[#153958] font-bold mb-2">
                            Mesa {{ $orden->mesa->nombre ?? 'No definida' }}
                        </h4>
                        <ul class="text-sm text-[#153958] list-disc ml-5 mb-4">
                            @foreach ($orden->productos as $producto)
                            <li>
                                {{ $producto['cantidad'] }}x {{ $producto['nombre'] }}
                                @if (!empty($producto['adiciones']))
                                <ul class="text-xs text-gray-600 list-disc ml-4">
                                    @foreach ($producto['adiciones'] as $adicion)
                                    <li>+ {{ $adicion['nombre'] }} (${{ number_format($adicion['precio'], 0, ',', '.') }})</li>
                                    @endforeach
                                </ul>
                                @endif
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    <span class="text-xs text-gray-500">{{ $orden->created_at->format('H:i') }}</span>
                </div>

                <div class="flex justify-between mt-2">
                    <form method="POST" action="{{ route('comandas.activar', $orden) }}" class="w-full">
                        @csrf
                        <button type="submit"
                            class="bg-[#FCD200] text-[#153958] font-bold py-2 px-4 rounded-md w-full hover:bg-yellow-300">
                            ACTIVAR MESA
                        </button>
                    </form>
                    <form method="POST" action="{{ route('comandas.desactivar', $orden) }}" class="ml-2">
                        @csrf
                        <button type="submit"
                            class="bg-gray-200 text-[#153958] font-semibold py-2 px-4 rounded-md hover:bg-gray-300">
                            Cancelar
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="bg-yellow-50 text-gray-500 italic p-6 rounded-md">
                No hay pedidos pendientes de activación.
            </div>
            @endforelse
        </div>

        {{-- Pedidos Confirmados --}}
        <div>
            <h3 class="text-lg font-bold text-[#3CB28B] flex items-center mb-3">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M5 13l4 4L19 7" />
                </svg>
                Pedidos Confirmados
            </h3>

            @forelse($ordenesConfirmadas as $orden)
            <div class="bg-green-50 border-l-4 border-[#3CB28B] shadow-md rounded-md p-4 mb-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="text-[#153958] font-bold mb-2">
                            Mesa {{ $orden->mesa->nombre ?? 'No definida' }}
                        </h4>
                        <ul class="text-sm text-[#153958] list-disc ml-5 mb-4">
                            @foreach ($orden->productos as $producto)
                            <li>{{ $producto['cantidad'] }}x {{ $producto['nombre'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <span class="text-xs text-gray-500">{{ $orden->created_at->format('H:i') }}</span>
                </div>
                <p class="text-sm text-[#3CB28B] font-semibold">Total: ${{ number_format($orden->total, 0, ',', '.') }}</p>
            </div>
            @empty
            <div class="bg-green-50 text-gray-500 italic p-6 rounded-md">
                No hay pedidos nuevos.
            </div>
            @endforelse
        </div>

    </div>
</div>
@endsection