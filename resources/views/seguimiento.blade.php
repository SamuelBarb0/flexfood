@extends('layouts.app')

@section('title', 'Seguimiento de Pedido')

@section('content')
<div class="max-w-xl mx-auto py-12 px-6 text-center">
    <img src="{{ asset('images/flexfood.png') }}" class="h-20 mx-auto mb-6" alt="Logo FlexFood">

    <div class="bg-white shadow rounded-lg p-6">
        <h1 class="text-2xl font-bold text-[#153958] mb-4">Estado de tu pedido – {{ $restaurante->nombre }}</h1>

        <p class="text-lg text-gray-700 mb-6">
            @if ($estado === 1)
                🧑‍🍳 <strong class="text-[#3CB28B]">Tu pedido está en proceso</strong>. Estamos preparando tu comida con mucho amor.
            @elseif ($estado === 2)
                ✅ <strong class="text-[#3CB28B]">Tu pedido fue entregado</strong>. ¡Buen provecho!
            @else
                ⏳ Esperando confirmación del restaurante...
            @endif
        </p>

        @if ($estado === 2)
        <a href="{{ route('cuenta.pedir', $restaurante) . '?mesa_id=' . $mesa_id }}"
           class="bg-[#153958] hover:bg-[#122e4a] text-white px-4 py-2 rounded">
            Pedir la cuenta
        </a>
        @endif
    </div>
</div>

<script>
(() => {
  const FORCE_RELOAD_MS = 5000;   // recarga forzada cada 5s
  const POLL_MS = 2000;           // polling del estado (opcional, recarga antes si cambia)

  let reloading = false;
  let estadoActual = Number({{ (int) $estado }});
  const urlEstado = "{{ route('ordenes.estadoActual', [$restaurante, $mesa_id]) }}";

  function doReload(reason) {
    if (reloading) return;
    reloading = true;
    const url = new URL(window.location.href);
    // cache-busting y motivo (útil si inspeccionas)
    url.searchParams.set('t', Date.now().toString());
    url.searchParams.set('r', reason);
    window.location.replace(url.toString());
  }

  async function poll() {
    if (reloading) return;
    try {
      const res = await fetch(urlEstado + '?t=' + Date.now(), {
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'Cache-Control': 'no-cache'
        },
        cache: 'no-store'
      });
      if (!res.ok) throw new Error('HTTP ' + res.status);

      const data = await res.json();
      const nuevo = Number(
        (data && data.estado != null) ? data.estado :
        (data && data.data && data.data.estado != null) ? data.data.estado :
        NaN
      );
      if (!Number.isNaN(nuevo) && nuevo !== estadoActual) {
        // cambio real de estado: recarga inmediata
        doReload('estado');
      }
    } catch (e) {
      // si falla el polling, igual habrá recarga forzada
      console.warn('Polling error:', e);
    }
  }

  // Recarga forzada cada 5s
  setInterval(() => doReload('tick'), FORCE_RELOAD_MS);
  // Polling para reaccionar antes si cambia el estado
  setInterval(poll, POLL_MS);
})();
</script>
@endsection
