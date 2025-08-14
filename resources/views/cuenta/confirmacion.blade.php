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

    setInterval(() => {
        fetch(`/estado-actual/${mesaId}`)
            .then(res => res.json())
            .then(data => {
                if (data.estado !== estadoActual && data.estado === 4) {
                    location.reload();
                }
            });
    }, 20000);
</script>
@endsection
