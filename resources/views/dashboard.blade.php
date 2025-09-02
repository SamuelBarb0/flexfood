@extends('layouts.app')

@section('title', 'Dashboard de Estado')

@section('content')
@php
    // Defaults seguros si el controlador no inyecta variables
    $restaurante      = $restaurante      ?? null;
    $mesasConEstado   = collect($mesasConEstado   ?? []);
    $categorias       = collect($categorias       ?? []);
    $ingresosTotales  = $ingresosTotales  ?? 0;

    // Construimos TODA la config fuera del atributo para que @json la serialice bien
    $dashboardOpts = [
        'finalizarUrl'       => $restaurante ? route('ordenes.finalizar', ['restaurante' => $restaurante->slug]) : null,
        'ticketEmailBase'    => url('/tickets'),
        'restauranteNombre'  => $restaurante->nombre ?? null,
        'categorias'         => $categorias,
        'tieneRestaurante'   => !empty($restaurante),
        'tieneDatos'         => $mesasConEstado->isNotEmpty() || $categorias->isNotEmpty(),
        'menuPublicoUrl'     => $restaurante ? route('menu.publico', ['restaurante' => $restaurante->slug]) : null,
    ];
@endphp

<div
    class="py-6 px-4 sm:px-6 lg:px-8 bg-gray-100 min-h-screen"
    x-data='dashboardTpv(@json($dashboardOpts))'  {{-- <— OJO: comillas simples afuera, JSON adentro --}}
>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Dashboard de Estado</h2>
        <h3 class="text-lg font-semibold text-gray-800">Estado de las Mesas</h3>
    </div>

    {{-- Empty state elegante cuando no hay restaurante o datos --}}
    @if (empty($restaurante) || ($mesasConEstado->isEmpty() && $categorias->isEmpty()))
        <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center">
            <div class="mx-auto mb-4 h-12 w-12 rounded-full bg-gray-100 flex items-center justify-center">
                <svg class="h-6 w-6 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M20 13V7a2 2 0 00-2-2h-4l-2-2H6a2 2 0 00-2 2v6m16 0v4a2 2 0 01-2 2h-3m5-6H4m9 6H6a2 2 0 01-2-2v-4" />
                </svg>
            </div>
            <h4 class="text-xl font-semibold text-gray-800">Sin restaurante asignado</h4>
            <p class="mt-1 text-gray-600">
                El administrador del restaurante debe crear tu cuenta o asignarte a un restaurante.
                <br>
                Ponte en contacto con el administrador para obtener acceso.
            </p>
        </div>
    @else
        {{-- Grid de mesas --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6 gap-4 mb-6">
            @forelse ($mesasConEstado as $mesa)
                @php
                    switch ((int)($mesa['estado'] ?? 0)) {
                        case 1: $estadoTexto = 'Activa';        $bg = 'bg-green-500 text-white'; break;
                        case 2: $estadoTexto = 'Ocupada';       $bg = 'bg-blue-500 text-white';  break;
                        case 3: $estadoTexto = 'Pide la cuenta';$bg = 'bg-orange-500 text-white';break;
                        default:$estadoTexto = 'Libre';         $bg = 'bg-gray-300 text-gray-800';break;
                    }
                @endphp

                <div class="{{ $bg }} rounded-lg p-4 text-center shadow-sm cursor-pointer"
                     @click="clickMesa(
                         @js($mesa['numero'] ?? null),
                         @js($estadoTexto),
                         @js($mesa['cuenta'] ?? []),
                         @js($mesa['orden_id'] ?? null),
                         @js($mesa['id'] ?? ($mesa['mesa_id'] ?? null))
                     )">
                    <div class="text-2xl font-bold">{{ $mesa['numero'] ?? '-' }}</div>
                    <div class="text-sm font-semibold mb-1 capitalize">{{ $estadoTexto }}</div>
                    <div class="text-sm">{{ $mesa['tiempo'] ?? '-' }}</div>
                    <div class="text-md font-bold mt-1">
                        {{ (($mesa['total'] ?? 0) > 0) ? number_format($mesa['total'], 2, ',', '.') . ' €' : '- €' }}
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <div class="rounded-lg border border-gray-200 bg-white p-6 text-center text-gray-600">
                        No hay mesas registradas todavía.
                    </div>
                </div>
            @endforelse
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-green-100 text-green-800 text-md font-bold px-6 py-4 rounded shadow">
                Ingresos Activos Totales: {{ number_format($ingresosTotales, 2, ',', '.') }} €
            </div>

            <div class="bg-white rounded shadow p-4">
                <h4 class="text-md font-semibold mb-2 text-gray-800 flex items-center">
                    <svg class="h-5 w-5 mr-2 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
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
    @endif
</div>
@endsection



<script>
function dashboardTpv(opts = {}) {
  // Endpoints (seguros cuando no hay restaurante)
  const ENDPOINTS = {
    finalizar: opts.finalizarUrl || null,
    ticketEmailBase: opts.ticketEmailBase || '',
    menuPublico: opts.menuPublicoUrl || null,
  };

  return {
    // estado UI
    mostrarModal: false,
    mostrarTicket: false,
    mesaSeleccionada: null,   // { numero, id }
    estadoMesa: '',
    emailCliente: '',
    emailDestino: '',
    cuentaActual: [],
    ticketActual: null,       // { id (orden), mesa, mesa_id, ... }
    categorias: opts.categorias || [],
    busqueda: '',
    ordenIdSeleccionada: null,

    // contexto
    tieneRestaurante: !!opts.tieneRestaurante,
    tieneDatos: !!opts.tieneDatos,
    restauranteNombre: opts.restauranteNombre || null,

    get categoriasFiltradas() {
      if (!this.busqueda.trim()) return this.categorias;
      return this.categorias
        .map(cat => ({
          ...cat,
          productos: (cat.productos || []).filter(p =>
            (p.nombre || '').toLowerCase().includes(this.busqueda.toLowerCase())
          )
        }))
        .filter(cat => (cat.productos || []).length > 0);
    },

    get totalCuenta() {
      return (this.cuentaActual || []).reduce((acc, item) => {
        const base = parseFloat(item.precio_base ?? item.precio) || 0;
        const adic = (item.adiciones ?? []).reduce((s, a) => s + (parseFloat(a.precio) || 0), 0);
        return acc + (base + adic) * (item.cantidad ?? 1);
      }, 0);
    },

    /**
     * Un solo manejador: si la mesa está LIBRE => redirige a menú público con mesa-id
     * si no, abre el modal de gestión de cuenta como siempre.
     */
    clickMesa(numero, estado, cuenta = [], ordenId = null, mesaId = null) {
      if (estado === 'Libre') {
        if (!this.tieneRestaurante || !ENDPOINTS.menuPublico) {
          alert('No hay restaurante o menú público disponible.');
          return;
        }
        try {
          const url = new URL(ENDPOINTS.menuPublico, window.location.origin);
          // usa "mesa-id" como pediste
          url.searchParams.set('mesa_id', mesaId ?? numero ?? '');
          window.location.assign(url.toString());
        } catch (e) {
          // Si por alguna razón ENDPOINTS.menuPublico ya es absoluto, fallback simple:
          const sep = ENDPOINTS.menuPublico.includes('?') ? '&' : '?';
          window.location.assign(`${ENDPOINTS.menuPublico}${sep}mesa_id=${encodeURIComponent(mesaId ?? numero ?? '')}`);
        }
        return;
      }

      // Si NO está libre -> comportamiento de abrir modal
      if (!this.tieneRestaurante) {
        alert('No hay restaurante asignado.');
        return;
      }

      this.mesaSeleccionada = { numero, id: mesaId };
      this.estadoMesa = estado;
      this.ordenIdSeleccionada = ordenId;

      this.cuentaActual = (cuenta || []).map(i => ({
        nombre: i.nombre,
        precio_base: parseFloat(i.precio_base ?? i.precio ?? 0) || 0,
        precio:      parseFloat(i.precio_base ?? i.precio ?? 0) || 0,
        cantidad: i.cantidad ?? 1,
        adiciones: i.adiciones ?? []
      }));

      this.mostrarModal = true;
    },

    agregarProducto(producto) {
      const existente = (this.cuentaActual || []).find(i =>
        i.nombre === producto.nombre &&
        JSON.stringify(i.adiciones ?? []) === JSON.stringify(producto.adiciones ?? [])
      );
      if (existente) {
        existente.cantidad += 1;
        return;
      }
      this.cuentaActual.push({
        nombre: producto.nombre,
        precio_base: parseFloat(producto.precio),
        precio:      parseFloat(producto.precio),
        cantidad: 1,
        adiciones: producto.adiciones ?? []
      });
    },

    cerrarMesa() {
      if (!this.tieneRestaurante) {
        alert('No hay restaurante asignado.');
        return;
      }
      if (!ENDPOINTS.finalizar) {
        alert('No se pudo determinar el endpoint de finalización.');
        return;
      }
      const mesaId = this.ticketActual?.mesa_id || this.mesaSeleccionada?.id;
      if (!mesaId) {
        alert('No se encontró el ID de la mesa. Abre la mesa desde el tablero para continuar.');
        return;
      }

      fetch(ENDPOINTS.finalizar, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ mesa_id: mesaId })
      })
      .then(async (response) => {
        const text = await response.text();
        const payload = text && text.trim().startsWith('{') ? JSON.parse(text) : {};
        if (!response.ok) throw new Error(`HTTP ${response.status}: ${text.slice(0,200)}`);
        return payload;
      })
      .then((data) => {
        if (data.success) {
          this.mostrarTicket = false;
          this.mostrarModal = false;
          this.mesaSeleccionada = null;
          this.estadoMesa = 'Libre';
          this.cuentaActual = [];
          this.ticketActual = null;
          this.ordenIdSeleccionada = null;
          this.emailDestino = '';
        } else {
          alert(data.message || 'Error al cerrar la mesa. Intenta nuevamente.');
        }
      })
      .catch(() => alert('Tienes procesos pendientes, ciérralos y vuelve a intentar'));
    },

    gestionarTicket() {
      this.mostrarModal = false;
      this.ticketActual = {
        id: this.ordenIdSeleccionada ?? null,
        restaurante_nombre: this.restauranteNombre || '',
        mesa: this.mesaSeleccionada?.numero,
        mesa_id: this.mesaSeleccionada?.id,
        fecha: new Date().toLocaleString(),
        productos: JSON.parse(JSON.stringify(this.cuentaActual || [])),
        total: this.totalCuenta
      };
      this.mostrarTicket = true;
    },

    generarPDFTicket() {
      const element = document.getElementById('ticket-printable');
      if (!element) return;

      const opt = {
        margin: 0,
        filename: `ticket_mesa_${this.ticketActual?.mesa ?? 's/n'}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, letterRendering: true },
        jsPDF: { unit: 'mm', format: 'a6', orientation: 'portrait' }
      };
      html2pdf().set(opt).from(element).save();
    },

    enviarTicketEmail() {
      if (!this.ticketActual?.id) {
        alert('No se encontró el ID de la orden. Abre la mesa con su orden asociada.');
        return;
      }
      if (!this.emailDestino || !/.+@.+\..+/.test(this.emailDestino)) {
        alert('Por favor ingresa un correo válido.');
        return;
      }

      const url = `${ENDPOINTS.ticketEmailBase}/${this.ticketActual.id}/enviar-email`;
      fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ email: this.emailDestino })
      })
      .then(async (res) => {
        const ct = res.headers.get('content-type') || '';
        const payload = ct.includes('application/json') ? await res.json() : { message: await res.text() };
        if (!res.ok) throw new Error(payload?.message || 'No se pudo enviar el ticket.');
        alert(payload?.message || 'Ticket enviado correctamente.');
      })
      .catch(err => {
        console.error(err);
        alert('Ocurrió un error al enviar el correo.');
      });
    },
  };
}
</script>
