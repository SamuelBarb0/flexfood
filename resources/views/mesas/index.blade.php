@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-[#F5F7FA] py-10 px-4">
    <div class="max-w-5xl mx-auto bg-white shadow rounded-lg p-8">
        <h1 class="text-2xl font-bold text-[#1A202C] mb-6">Gestión de Mesas y Códigos QR</h1>

        <div class="mb-6">
            <label for="cantidad" class="block text-sm text-gray-700 font-medium mb-1">
                Introduce el número total de mesas en tu restaurante para generar los códigos QR correspondientes.
            </label>
            <div class="flex gap-4 items-center">
                <input type="number" id="cantidad" min="0"
                    class="w-40 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                <button onclick="crearMesas()"
                    class="inline-flex items-center px-5 py-2.5 text-white font-semibold rounded-md shadow hover:opacity-90 transition"
                    style="background-color: #61b299;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 4v16m8-8H4" />
                    </svg>
                    Generar Códigos
                </button>
            </div>
        </div>

        <hr class="my-6">

        <h2 class="text-lg font-semibold text-gray-800 mb-2">Previsualización para Imprimir</h2>
        <p class="text-sm text-gray-500 mb-4">Aquí aparecerán los códigos QR. Usa el botón de abajo para una vista de impresión.</p>

        <div id="gridMesas" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
            @foreach ($mesas as $mesa)
            <div class="flex flex-col items-center border border-gray-200 p-4 rounded-md shadow-sm bg-white hover:shadow-md transition">
                <img src="{{ asset('images/qrmesas/' . $mesa->codigo_qr) }}"
                    alt="QR Mesa {{ $mesa->nombre }}"
                    class="w-24 h-24 object-contain mb-2">
                <span class="text-sm font-semibold text-gray-700">Mesa N.º {{ $mesa->nombre }}</span>
            </div>
            @endforeach
        </div>

        <div class="mt-10 text-center">
            <a href="{{ route('mesas.imprimirHoja') }}" target="_blank"
                class="inline-flex items-center justify-center px-6 py-3 bg-gray-800 text-white rounded-md hover:bg-gray-900 shadow transition">
                🖨️ Abrir Vista de Impresión
            </a>
        </div>
    </div>
</div>
@endsection

<script>
    function crearMesas() {
        const cantidad = document.getElementById('cantidad').value;

        fetch("{{ route('mesas.crearAjax') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    cantidad
                })
            })
            .then(res => res.json())
            .then(data => {
                const grid = document.getElementById('gridMesas');
                grid.innerHTML = ''; // ✅ Limpia todas las mesas actuales

                if (data.mesas) {
                    data.mesas.forEach(mesa => {
                        const contenedor = document.createElement('div');
                        contenedor.className = "flex flex-col items-center border border-gray-200 p-4 rounded-md shadow-sm bg-white hover:shadow-md transition";

                        const img = document.createElement('img');
                        img.src = mesa.qr_url;
                        img.className = "w-24 h-24 object-contain mb-2";

                        const nombre = document.createElement('span');
                        nombre.className = "text-sm font-semibold text-gray-700";
                        nombre.innerText = 'Mesa N.º ' + mesa.nombre;

                        contenedor.appendChild(img);
                        contenedor.appendChild(nombre);

                        grid.appendChild(contenedor);
                    });
                }

                alert(data.message);
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Hubo un problema al actualizar las mesas');
            });
    }
</script>