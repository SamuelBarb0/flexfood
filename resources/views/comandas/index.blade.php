@extends('layouts.app')

@section('content')
<div class="px-6 py-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Panel de Comandas – {{ $restaurante->nombre }}</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- 🟡 Mesas por Activar --}}
        @if(auth()->user()->hasRole('administrador') || auth()->user()->hasRole('cocina'))
        <div>
            <h3 class="text-lg font-bold text-yellow-600 flex items-center mb-3">
                ...
                🕓 Mesas por Activar
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
                    {{-- AJAX: paso la URL ya resuelta para cada orden --}}
                    <form class="form-activar"
                          data-url="{{ route('comandas.activar', [$restaurante, $orden]) }}">
                        @csrf
                        <button type="submit"
                                class="bg-[#FCD200] text-[#153958] font-bold py-2 px-4 rounded-md w-full hover:bg-yellow-300">
                            ACTIVAR MESA
                        </button>
                    </form>

                    <form method="POST" action="{{ route('comandas.desactivar', [$restaurante, $orden]) }}" class="ml-2">
                        @csrf
                        <button type="submit"
                                class="bg-gray-200 text-[#153958] font-semibold py-2 px-4 rounded-md hover:bg-gray-300">
                            🗑️ Cancelar Pedido
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
        @endif

        {{-- 🔵 Mesas en Preparación --}}
        @if(auth()->user()->hasRole('administrador') || auth()->user()->hasRole('mesero'))
        <div>
            <h3 class="text-lg font-bold text-blue-600 flex items-center mb-3">
                ...
                👨‍🍳 Mesas en Preparación
            </h3>

            @forelse($ordenesEnProceso as $orden)
            <div class="bg-blue-50 border-l-4 border-blue-500 shadow-md rounded-md p-4 mb-4">
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

                <form method="POST" action="{{ route('comandas.entregar', [$restaurante, $orden]) }}" class="mt-3">
                    @csrf
                    <button type="submit"
                            class="bg-blue-500 text-white font-bold py-2 px-4 rounded-md w-full hover:bg-blue-600">
                        🍽️ ENTREGAR A MESA
                    </button>
                </form>
            </div>
            @empty
            <div class="bg-blue-50 text-gray-500 italic p-6 rounded-md">
                No hay mesas en preparación.
            </div>
            @endforelse
        </div>
        @endif

        {{-- ✅ Mesas Servidas --}}
        @if(auth()->user()->hasRole('administrador'))
        <div>
            <h3 class="text-lg font-bold text-[#3CB28B] flex items-center mb-3">
                ...
                ✅ Mesas Servidas
            </h3>

            @forelse($ordenesEntregadas as $orden)
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
                No hay mesas servidas.
            </div>
            @endforelse
        </div>
        @endif

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const activarForms = document.querySelectorAll('.form-activar');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // URL con slug del restaurante
        const urlNuevas = "{{ route('comandas.nuevas', $restaurante) }}";

        function verificarNuevasComandas() {
            fetch(urlNuevas, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.nuevas !== undefined) {
                    Alpine.store('ordenes').nuevas = data.nuevas;
                    localStorage.setItem('ordenesNuevas', data.nuevas);
                }
            })
            .catch(err => console.error('Error consultando nuevas comandas:', err));
        }

        activarForms.forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const url = this.dataset.url; // viene de data-url en el form
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(res => {
                    if (!res.ok) throw new Error("Error activando mesa");
                    return res.json();
                })
                .then(() => {
                    verificarNuevasComandas();
                    location.reload();
                })
                .catch(err => console.error('Error activando mesa:', err));
            });
        });
    });
</script>

@endsection
