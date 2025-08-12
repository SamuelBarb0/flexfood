@extends('layouts.app')

@section('title', 'Dashboard de Estado')

@section('content')
<div
    class="py-6 px-4 sm:px-6 lg:px-8 bg-gray-100 min-h-screen"
    x-data="dashboardTpv()">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Dashboard de Estado</h2>
        <h3 class="text-lg font-semibold text-gray-800">Estado de las Mesas</h3>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6 gap-4 mb-6">
        @foreach ($mesasConEstado as $mesa)
        @php
        switch ($mesa['estado']) {
        case 1:
        $estadoTexto = 'Activa';
        $bg = 'bg-green-500 text-white';
        break;
        case 2:
        $estadoTexto = 'Ocupada';
        $bg = 'bg-blue-500 text-white';
        break;
        case 3:
        $estadoTexto = 'Pide la cuenta';
        $bg = 'bg-orange-500 text-white';
        break;
        default:
        $estadoTexto = 'Libre';
        $bg = 'bg-gray-300 text-gray-800';
        break;
        }
        @endphp

        <div class="{{ $bg }} rounded-lg p-4 text-center shadow-sm cursor-pointer"
            @click="['Activa', 'Ocupada', 'Pide la cuenta'].includes('{{ $estadoTexto }}') && abrirModalMesa({{ $mesa['numero'] }}, '{{ $estadoTexto }}', {{ json_encode($mesa['cuenta'] ?? []) }})">
            <div class="text-2xl font-bold">{{ $mesa['numero'] }}</div>
            <div class="text-sm font-semibold mb-1 capitalize">{{ $estadoTexto }}</div>
            <div class="text-sm">{{ $mesa['tiempo'] ?? '-' }}</div>
            <div class="text-md font-bold mt-1">
                {{ $mesa['total'] > 0 ? number_format($mesa['total'], 2, ',', '.') . ' €' : '- €' }}
            </div>
        </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-green-100 text-green-800 text-md font-bold px-6 py-4 rounded shadow">
            Ingresos Activos Totales: {{ number_format($ingresosTotales, 2, ',', '.') }} €
        </div>

        <div class="bg-white rounded shadow p-4">
            <h4 class="text-md font-semibold mb-2 text-gray-800 flex items-center">
                <svg class="h-5 w-5 mr-2 text-gray-600" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8 7V3m8 4V3m-9 4h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Actividad Reciente
            </h4>
            <p class="text-sm text-gray-500">Sin actividad reciente</p>
        </div>
    </div>

    @include('partials.modal-tpv')
    @include('partials.modal-ticket')
</div>
@endsection

<script>
    function dashboardTpv() {
        // Endpoints con slug del restaurante (Blade resuelve la URL)
        const ENDPOINTS = {
            finalizar: "{{ route('ordenes.finalizar', $restaurante) }}",
        };

        return {
            mostrarModal: false,
            mostrarTicket: false,
            mesaSeleccionada: null,
            estadoMesa: '',
            emailCliente: '',
            cuentaActual: [],
            ticketActual: null,
            categorias: @json($categorias), // ya lo tienes cargado arriba
            busqueda: '',

            get categoriasFiltradas() {
                if (!this.busqueda.trim()) return this.categorias;

                return this.categorias.map(cat => ({
                    ...cat,
                    productos: cat.productos.filter(prod =>
                        prod.nombre.toLowerCase().includes(this.busqueda.toLowerCase())
                    )
                })).filter(cat => cat.productos.length > 0);
            },

            get totalCuenta() {
                return this.cuentaActual.reduce((acc, item) => {
                    const base = parseFloat(item.precio_base ?? item.precio) || 0;
                    const totalAdiciones = item.adiciones
                        ? item.adiciones.reduce((sum, a) => sum + parseFloat(a.precio || 0), 0)
                        : 0;
                    return acc + (base + totalAdiciones) * item.cantidad;
                }, 0);
            },

            abrirModalMesa(numero, estado, cuenta = []) {
                if (estado !== 'Libre') {
                    this.mesaSeleccionada = numero;
                    this.estadoMesa = estado;
                    this.cuentaActual = cuenta.map(i => ({
                        nombre: i.nombre,
                        precio_base: parseFloat(i.precio_base ?? i.precio ?? 0) || 0,
                        precio: parseFloat(i.precio_base ?? i.precio ?? 0) || 0,
                        cantidad: i.cantidad ?? 1,
                        adiciones: i.adiciones ?? []
                    }));
                    this.mostrarModal = true;
                }
            },

            agregarProducto(producto) {
                let existente = this.cuentaActual.find(i =>
                    i.nombre === producto.nombre &&
                    JSON.stringify(i.adiciones ?? []) === JSON.stringify(producto.adiciones ?? [])
                );

                if (existente) {
                    existente.cantidad += 1;
                } else {
                    this.cuentaActual.push({
                        nombre: producto.nombre,
                        precio_base: parseFloat(producto.precio),
                        precio: parseFloat(producto.precio),
                        cantidad: 1,
                        adiciones: producto.adiciones ?? []
                    });
                }
            },

            cerrarMesa() {
                console.log("🚀 Iniciando cierre de mesa...");

                if (!this.ticketActual || !this.ticketActual.mesa) {
                    console.warn("⚠️ No hay ticket o mesa seleccionada.");
                    return;
                }

                const numeroMesa = this.ticketActual.mesa;
                console.log("🪑 Mesa a cerrar:", numeroMesa);

                fetch(ENDPOINTS.finalizar, {
                    method: 'POST',
                    credentials: 'same-origin', // 👈 asegura cookie de sesión
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ mesa: numeroMesa })
                })
                .then(response => {
                    console.log("📡 Respuesta recibida del backend", response);
                    if (!response.ok) throw new Error('HTTP ' + response.status);
                    return response.json();
                })
                .then(data => {
                    console.log("📦 Respuesta JSON:", data);

                    if (data.success) {
                        console.log("✅ Mesa cerrada exitosamente");
                        this.mostrarTicket = false;
                        this.mostrarModal = false;
                        this.mesaSeleccionada = null;
                        this.estadoMesa = 'Libre';
                        this.cuentaActual = [];
                        this.ticketActual = null;
                    } else {
                        console.error("❌ No se pudo cerrar la mesa:", data.message || 'Error desconocido');
                        alert("Error al cerrar la mesa. Intenta nuevamente.");
                    }
                })
                .catch(error => {
                    console.error("💥 Error en la solicitud:", error);
                    alert("Error al comunicarse con el servidor.");
                });
            },

            gestionarTicket() {
                this.mostrarModal = false;
                this.ticketActual = {
                    mesa: this.mesaSeleccionada,
                    fecha: new Date().toLocaleString(),
                    productos: JSON.parse(JSON.stringify(this.cuentaActual)),
                    total: this.totalCuenta
                };
                this.mostrarTicket = true;
            },

            generarPDFTicket() {
                const element = document.getElementById('ticket-printable');
                const heightPx = element.offsetHeight;
                const heightMm = heightPx * 0.264583 + 20; // margen extra

                const opt = {
                    margin: [5, 5, 5, 5],
                    filename: `ticket_mesa_${this.ticketActual.mesa}.pdf`,
                    image: { type: 'jpeg', quality: 1 },
                    html2canvas: { scale: 2 },
                    jsPDF: { unit: 'mm', format: [80, heightMm], orientation: 'portrait' }
                };

                html2pdf().set(opt).from(element).save();
            },
        };
    }
</script>
