@extends('layouts.app')

@section('title', 'Dashboard de Estado')
<style>
/* Sandbox para renderizar el clon sin estorbar la UI */
.pdf-sandbox{
  position: fixed;
  left: 0;           /* 👈 X = 0 (importante) */
  top: -10000px;     /* 👈 fuera de la vista en Y */
  width: 302px;      /* ≈ 80mm */
  background: #fff;
  z-index: -1;
}

/* Layout fijo del ticket al exportar */
#ticket-printable.for-pdf,
#ticket-printable.for-pdf * { box-sizing: border-box; }

#ticket-printable.for-pdf{
  width: 302px;
  max-width: 302px;
  margin: 0 !important;
  border: 0;
  box-shadow: none !important;
  background: #fff;
}
</style>
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
    @click="
        ['Activa', 'Ocupada', 'Pide la cuenta'].includes(@js($estadoTexto)) &&
        abrirModalMesa(
            @js($mesa['numero']),                 // número visible
            @js($estadoTexto),
            @js($mesa['cuenta'] ?? []),
            @js($mesa['orden_id'] ?? null),       // id de la orden (si hay)
            @js($mesa['id'] ?? ($mesa['mesa_id'] ?? null)) // <-- id real de la mesa
        )
    ">
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
  // Endpoints con slug
  const ENDPOINTS = {
    finalizar: "{{ route('ordenes.finalizar', ['restaurante' => $restaurante->slug]) }}",
    ticketEmailBase: "{{ url('/tickets') }}",
  };

  return {
    mostrarModal: false,
    mostrarTicket: false,
    mesaSeleccionada: null,   // { numero, id }
    estadoMesa: '',
    emailCliente: '',
    emailDestino: '',
    cuentaActual: [],
    ticketActual: null,       // { id (orden), mesa, mesa_id, ... }
    categorias: @json($categorias),
    busqueda: '',
    ordenIdSeleccionada: null,

    get categoriasFiltradas() {
      if (!this.busqueda.trim()) return this.categorias;
      return this.categorias
        .map(cat => ({
          ...cat,
          productos: cat.productos.filter(p =>
            p.nombre.toLowerCase().includes(this.busqueda.toLowerCase())
          )
        }))
        .filter(cat => cat.productos.length > 0);
    },

    get totalCuenta() {
      return this.cuentaActual.reduce((acc, item) => {
        const base = parseFloat(item.precio_base ?? item.precio) || 0;
        const adic = (item.adiciones ?? []).reduce((s, a) => s + parseFloat(a.precio || 0), 0);
        return acc + (base + adic) * (item.cantidad ?? 1);
      }, 0);
    },

    // ahora recibe mesaId real
    abrirModalMesa(numero, estado, cuenta = [], ordenId = null, mesaId = null) {
      if (estado === 'Libre') return;

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
      const existente = this.cuentaActual.find(i =>
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
        body: JSON.stringify({ mesa_id: mesaId }) // <-- ahora enviamos mesa_id
      })
      .then(async (response) => {
        const text = await response.text();
        const payload = text && text.startsWith('{') ? JSON.parse(text) : {};
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
      .catch(() => alert('Error al comunicarse con el servidor.'));
    },

    // Construye el ticket para previsualizar y enviar
    gestionarTicket() {
      this.mostrarModal = false;
      this.ticketActual = {
        id: this.ordenIdSeleccionada ?? null,
        restaurante_nombre: (window.RESTAURANTE_NOMBRE || {{ Js::from($restaurante->nombre) }}),
        mesa: this.mesaSeleccionada?.numero,     // visible
        mesa_id: this.mesaSeleccionada?.id,      // FK real (clave)
        fecha: new Date().toLocaleString(),
        productos: JSON.parse(JSON.stringify(this.cuentaActual)),
        total: this.totalCuenta
      };
      this.mostrarTicket = true;
    },

async generarPDFTicket() {
  const PX_TO_MM = 0.264583;
  const ROLLO_MM = 80;
  const ROLLO_PX = Math.round(ROLLO_MM / PX_TO_MM); // ≈ 302 px

  const BUFFER_MM = 8; // 👈 aire extra para que nunca se corte

  const original = document.getElementById('ticket-printable');
  if (!original) return;

  if (document.fonts && document.fonts.ready) {
    await document.fonts.ready;
  }

  // sandbox
  const sandbox = document.createElement('div');
  sandbox.className = 'pdf-sandbox';
  document.body.appendChild(sandbox);

  // clon con layout fijo
  const clone = original.cloneNode(true);
  clone.id = 'ticket-printable-pdf';
  clone.classList.add('for-pdf');
  sandbox.appendChild(clone);

  await new Promise(r => requestAnimationFrame(r));

  const heightPx = Math.ceil(clone.getBoundingClientRect().height);
  const heightMm = Math.ceil(heightPx * PX_TO_MM);

  const opt = {
    margin: 0,
    filename: `ticket_mesa_${this.ticketActual?.mesa ?? ''}.pdf`,
    image: { type: 'jpeg', quality: 1 },
    html2canvas: {
      scale: 3,
      useCORS: true,
      letterRendering: true,
      windowWidth: ROLLO_PX,
      width: ROLLO_PX
    },
    // 👇 sumamos buffer al alto real medido
    jsPDF: { unit: 'mm', format: [ROLLO_MM, heightMm + BUFFER_MM], orientation: 'portrait' }
  };

  try {
    await html2pdf().set(opt).from(clone).save();
  } finally {
    sandbox.remove();
  }
},

    // Enviar ticket por email
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

