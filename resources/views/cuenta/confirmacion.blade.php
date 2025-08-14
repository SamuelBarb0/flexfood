@extends('layouts.app')

@section('title', 'Cuenta solicitada')

@section('content')
<div class="max-w-xl mx-auto py-12 px-6 text-center">
    <img src="{{ asset('images/flexfood.png') }}" class="h-20 mx-auto mb-6" alt="Logo FlexFood">

    <div class="bg-white shadow rounded-lg p-6">
        @if ($estado === 4)
            <h1 class="text-2xl font-bold text-[#153958] mb-4">🎉 Gracias por pedir en FlexFood</h1>
            <p class="text-lg text-gray-700 mb-6">
                Esperamos que hayas disfrutado tu experiencia. ¡Te esperamos pronto!
            </p>
        @else
            <h1 class="text-2xl font-bold text-[#153958] mb-4">Tu cuenta ha sido solicitada</h1>
            <p class="text-lg text-gray-700 mb-6">
                Un mesero pronto se acercará a tu mesa. ¡Gracias por tu visita!
            </p>
        @endif
    </div>
</div>

<script>
    const estadoActual = {{ $estado }};
    const mesaId = {{ $mesa_id }};

    // Extrae el slug desde la URL actual: /r/{slug}/...
    const parts = location.pathname.split('/'); // ["", "r", "{slug}", ...]
    const slug = parts[2] || 'flexfood'; // fallback si hace falta

    setInterval(() => {
        fetch(`/r/${slug}/estado-actual/${mesaId}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(async (res) => {
            const ct = res.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                const text = await res.text();
                throw new Error('Respuesta no JSON (¿login/419/500?): ' + text.slice(0, 200));
            }
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.message || 'Error HTTP ' + res.status);
            }
            return res.json();
        })
        .then((data) => {
            if (data.estado !== estadoActual && data.estado === 4) {
                location.reload();
            }
        })
        .catch((e) => console.error(e));
    }, 10000);
</script>

@endsection
