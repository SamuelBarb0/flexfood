@extends('layouts.app')

@section('content')
<div class="px-6 py-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Analíticas e Informes</h2>

    {{-- Secciones superiores --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

        {{-- 🏆 Ranking de Platos --}}
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Ranking de Platos Más Pedidos</h3>
            <ol class="list-decimal ml-5 text-gray-700 space-y-1">
                @forelse ($ranking as $producto)
                    <li><strong>{{ $producto->nombre }}</strong> - {{ $producto->total }} uds.</li>
                @empty
                    <li>No hay datos para hoy.</li>
                @endforelse
            </ol>
        </div>

        {{-- 📊 Volumen por hora --}}
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Volumen de Pedidos por Hora</h3>
            <canvas id="graficoPedidos" height="200"></canvas>
        </div>
    </div>

    {{-- 💰 Resumen de Caja --}}
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-semibold mb-4">Resumen de Caja del Día</h3>

        @forelse ($caja as $orden)
        <div class="flex justify-between items-center border-b py-3 text-sm text-gray-700">
            <div>
                <strong>Mesa {{ $orden->mesa->nombre ?? 'N/A' }}</strong><br>
                <span class="text-xs text-gray-500">Cerrada a las: {{ $orden->updated_at->format('H:i:s') }}</span>
            </div>
            <div class="font-semibold text-right">
                {{ number_format($orden->total, 2) }} €
            </div>
        </div>
        @empty
        <p class="text-gray-500">No se han cerrado órdenes hoy.</p>
        @endforelse

        <div class="text-right font-bold text-xl mt-4">
            Total Caja: {{ number_format($caja->sum('total'), 2) }} €
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const ctx = document.getElementById('graficoPedidos').getContext('2d');

    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [...Array(24).keys()].map(h => `${h.toString().padStart(2, '0')}:00`),
            datasets: [{
                label: 'Pedidos',
                data: @json($datosGrafico),
                backgroundColor: '#3cb28b',
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
</script>
@endpush
