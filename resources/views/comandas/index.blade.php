@extends('layouts.app')

@section('content')
<div class="px-6 py-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">
        Panel de Comandas – {{ $restaurante->nombre }}
    </h2>

    {{-- Aquí imprimimos y definimos la sección a la vez --}}
    @section('__grid')
    <div id="grid-comandas" class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- 🟡 Mesas por Activar --}}
        @if(auth()->user()->hasRole('administrador') || auth()->user()->hasRole('cocina') || auth()->user()->hasRole('restauranteadmin'))
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
                    <form class="form-activar" data-url="{{ route('comandas.activar', [$restaurante, $orden]) }}">
                        @csrf
                        <button type="submit"
                                class="bg-[#FCD200] text-[#153958] font-bold py-2 px-4 rounded-md w-full hover:bg-yellow-300">
                            ACTIVAR MESA
                        </button>
                    </form>

                    <form method="POST" action="{{ route('comandas.desactivar', [$restaurante, $orden]) }}" class="ml-2 form-cancelar">
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
        @if(auth()->user()->hasRole('administrador') || auth()->user()->hasRole('mesero') || auth()->user()->hasRole('restauranteadmin'))
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

                <form method="POST" action="{{ route('comandas.entregar', [$restaurante, $orden]) }}" class="mt-3 form-entregar">
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
        @if(auth()->user()->hasRole('administrador') || auth()->user()->hasRole('restauranteadmin'))
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
                <p class="text-sm text-[#3CB28B] font-semibold">
                    Total: ${{ number_format($orden->total, 0, ',', '.') }}
                </p>
            </div>
            @empty
            <div class="bg-green-50 text-gray-500 italic p-6 rounded-md">
                No hay mesas servidas.
            </div>
            @endforelse
        </div>
        @endif

    </div>
    @show
</div>

{{-- ==== LIVE REFRESH (Polling + AJAX en formularios) ==== --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const basePanel = "{{ route('comandas.panel', $restaurante) }}";
  const urlNuevas = "{{ route('comandas.nuevas', $restaurante) }}";

  async function refrescarPanel() {
    try {
      const r = await fetch(`${basePanel}?t=${Date.now()}`, {
        method: 'GET',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      if (!r.ok) throw new Error('HTTP ' + r.status);
      const html = await r.text();

      const tmp = document.createElement('div');
      tmp.innerHTML = html;
      const nuevoGrid = tmp.querySelector('#grid-comandas');
      if (!nuevoGrid) return;

      const actual = document.querySelector('#grid-comandas');
      if (actual && actual.innerHTML.trim() !== nuevoGrid.innerHTML.trim()) {
        actual.replaceWith(nuevoGrid);
        wireUpActions();
      }
    } catch (e) { console.error('Error refrescando panel:', e); }
  }

  async function verificarNuevasComandas() {
    try {
      const res = await fetch(urlNuevas, {
        credentials: 'same-origin',
        cache: 'no-store',
        headers: { 'Accept': 'application/json' }
      });
      if (!res.ok) return;
      const data = await res.json();
      if (data.nuevas !== undefined && window.Alpine?.store) {
        Alpine.store('ordenes').nuevas = data.nuevas;
        localStorage.setItem('ordenesNuevas', data.nuevas);
      }
    } catch (_) {}
  }

  function wireUpActions() {
    document.querySelectorAll('.form-activar').forEach((form) => {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const url = form.dataset.url;
        try {
          const res = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
          });
          if (!res.ok) throw new Error('Error activando mesa');
          await refrescarPanel();
          verificarNuevasComandas();
        } catch (err) { console.error(err); }
      }, { once: true });
    });

    document.querySelectorAll('.form-cancelar').forEach((form) => {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const url = form.action;
        try {
          const res = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
          });
          if (!res.ok) throw new Error('Error cancelando pedido');
          await refrescarPanel();
        } catch (err) { console.error(err); }
      }, { once: true });
    });

    document.querySelectorAll('.form-entregar').forEach((form) => {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const url = form.action;
        try {
          const res = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
          });
          if (!res.ok) throw new Error('Error entregando a mesa');
          await refrescarPanel();
        } catch (err) { console.error(err); }
      }, { once: true });
    });
  }

  setInterval(refrescarPanel, 6000);
  wireUpActions();
});
</script>
@endsection
