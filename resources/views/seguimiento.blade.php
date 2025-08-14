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
    (function() {
        let estadoActual = {{ $estado }};
        const urlEstado = "{{ route('ordenes.estadoActual', [$restaurante, $mesa_id]) }}";

        function poll() {
            fetch(urlEstado, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            })
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const ct = res.headers.get('content-type') || '';
                if (!ct.includes('application/json')) throw new Error('Respuesta no JSON');
                return res.json();
            })
            .then(data => {
                if (typeof data.estado !== 'undefined' && data.estado !== estadoActual) {
                    location.reload();
                }
            })
            .catch(err => console.error('Error consultando estado:', err));
        }

        poll();
        setInterval(poll, 10000);
    })();
</script>
@endsection
