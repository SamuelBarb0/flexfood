<!-- ===== SCRIPT Alpine (debe cargarse antes del HTML del carrito) ===== -->
<script>
window.menuCarrito = function () {
  const ENDPOINTS = {
    store: "{{ route('comandas.store', $restaurante) }}",
    seguimiento: "{{ route('seguimiento', $restaurante) }}",
    entregados: "{{ route('comandas.entregadas', $restaurante) }}",
    pedirCuentaPedido: "{{ route('cuenta.pedirPedido', $restaurante) }}", // GET ?mesa_id=&orden_id=
  };

  return {
    // --- Estado principal ---
    carrito: [],
    mostrarCarrito: false,
    modalProducto: false,
    productoSeleccionado: null,

    // Mesa desde URL (o fallback localStorage)
    mesa_id: null,

    // Otros UI flags
    mostrarGraciasModal: false,
    mostrarVideos: false,
    categorias: @json($categorias->pluck('id')),
    activeCategory: null,

    // Entregados (2) + Cuenta solicitada (3)
    pedidosEntregados: [],
    cargandoEntregados: false,

    // ---- STUBS del drawer de mesas (si el HTML aún los referencia) ----
    mostrarMesas: false,
    mesaSeleccionada: null,
    filtroMesa: "",
    coincideFiltro() { return true; },
    setMesa(m) { this.mesaSeleccionada = m; },
    quitarMesa() { this.mesaSeleccionada = null; },
    confirmarMesa() {
      const id = this.mesaSeleccionada?.id ?? null;
      this.mesa_id = id;
      try {
        if (id) {
          localStorage.setItem('ff_mesa_id', String(id));
          localStorage.setItem('ff_mesa_obj', JSON.stringify(this.mesaSeleccionada));
        } else {
          localStorage.removeItem('ff_mesa_id');
          localStorage.removeItem('ff_mesa_obj');
        }
      } catch (_) {}
      this.mostrarMesas = false;
    },

    // ==== cerrar todos los modales ====
    cerrarModales() {
      this.mostrarCarrito = false;
      this.modalProducto = false;
      this.mostrarGraciasModal = false;
      this.mostrarVideos = false;
      this.productoSeleccionado = null;
    },

    // ==== Derivados / helpers ====
    // ¿Hay algún pedido con cuenta solicitada (estado 3)?
    get hayCuentaAbierta() {
      return this.pedidosEntregados.some(p => Number(p.estado) === 3);
    },

    // ¿Este pedido puede pedir cuenta? (solo si está en 2 y NO hay otro en 3)
    puedePedirCuenta(ped) {
      return Number(ped?.estado) === 2 && !this.hayCuentaAbierta;
    },

    // ==== Ciclo de vida ====
    init() {
      const params = new URLSearchParams(window.location.search);
      const mesaParam = params.get('mesa_id');

      this.mostrarVideos = false;
      this.mostrarCarrito = false;
      this.modalProducto = false;
      this.mostrarGraciasModal = false;

      this.activeCategory = this.categorias[0];

      if (mesaParam) {
        this.mesa_id = mesaParam.replace(/\D+/g, '');
        try { localStorage.setItem('ff_mesa_id', String(this.mesa_id)); } catch(_) {}
      } else {
        try {
          const savedMesaId = localStorage.getItem('ff_mesa_id');
          if (savedMesaId) this.mesa_id = savedMesaId;
        } catch(_) {}
      }

      try { window.__FF = this; } catch(_) {}

      if (this.mesa_id) this.cargarEntregados();

      try {
        this.$watch('mostrarCarrito', (open) => {
          if (open && this.mesa_id) this.cargarEntregados();
        });
      } catch(_) {}
    },

    // ==== Productos/Carrito ====
    abrirDetalle(producto) {
      this.productoSeleccionado = { ...producto, adiciones: [] };
      this.modalProducto = true;
    },

    calcularPrecioTotal() {
      if (!this.productoSeleccionado) return 0;
      let base = parseFloat(this.productoSeleccionado.precio) || 0;
      let extras = (this.productoSeleccionado.adiciones || [])
        .reduce((acc, a) => acc + parseFloat(a.precio || 0), 0);
      return base + extras;
    },

    agregarConAdiciones() {
      if (!this.productoSeleccionado) return;
      const { id, nombre, precio, adiciones } = this.productoSeleccionado;
      const itemExistente = this.carrito.find(p =>
        p.id === id &&
        JSON.stringify(p.adiciones || []) === JSON.stringify(adiciones || [])
      );
      if (itemExistente) {
        itemExistente.cantidad++;
      } else {
        this.carrito.push({
          id,
          nombre,
          precio_base: parseFloat(precio),
          cantidad: 1,
          adiciones: [...(adiciones || [])]
        });
      }
      this.modalProducto = false;
    },

    agregarAlCarrito(id, nombre, precio_base) {
      const existente = this.carrito.find(p =>
        p.id === id && (!p.adiciones || p.adiciones.length === 0)
      );
      if (existente) {
        existente.cantidad++;
      } else {
        this.carrito.push({
          id,
          nombre,
          precio_base: parseFloat(precio_base),
          cantidad: 1,
          adiciones: []
        });
      }
    },

    quitarDelCarrito(id, adiciones = []) {
      const index = this.carrito.findIndex(p =>
        p.id === id &&
        JSON.stringify(p.adiciones || []) === JSON.stringify(adiciones || [])
      );
      if (index !== -1) {
        if (this.carrito[index].cantidad > 1) {
          this.carrito[index].cantidad--;
        } else {
          this.carrito.splice(index, 1);
        }
      }
    },

    cantidadEnCarrito(id) {
      return this.carrito
        .filter(p => p.id === id)
        .reduce((acc, item) => acc + item.cantidad, 0);
    },

    incrementarLinea(item) {
      const linea = this.carrito.find(p =>
        p.id === item.id &&
        JSON.stringify(p.adiciones || []) === JSON.stringify(item.adiciones || [])
      );
      if (linea) linea.cantidad++;
    },

    get resumenPorProducto() {
      const map = {};
      for (const i of this.carrito) {
        map[i.nombre] = (map[i.nombre] || 0) + i.cantidad;
      }
      return Object.entries(map).map(([nombre, cantidad]) => ({ nombre, cantidad }));
    },

    redirigirPedido() {
      const url = new URL(ENDPOINTS.seguimiento, window.location.origin);
      if (this.mesa_id) url.searchParams.set('mesa_id', this.mesa_id);
      window.location.href = url.toString();
    },

    get totalCantidad() {
      return this.carrito.reduce((acc, item) => acc + item.cantidad, 0);
    },

    get totalPrecio() {
      return this.carrito.reduce((acc, item) => {
        const precioBase = item.precio_base * item.cantidad;
        const adiciones = item.adiciones
          ? item.adiciones.reduce((suma, a) => suma + parseFloat(a.precio || 0), 0) * item.cantidad
          : 0;
        return acc + precioBase + adiciones;
      }, 0);
    },

    enviarPedido() {
      if (!this.carrito.length) { alert('Tu carrito está vacío.'); return; }
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
      fetch(ENDPOINTS.store, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          mesa_id: this.mesa_id || null,
          carrito: this.carrito
        })
      })
      .then(async (res) => {
        const ct = res.headers.get('content-type') || '';
        if (!res.ok || !ct.includes('application/json')) {
          const txt = await res.text();
          throw new Error(`HTTP ${res.status}: ${txt.slice(0,200)}`);
        }
        return res.json();
      })
      .then(() => {
        this.carrito = [];
        this.mostrarCarrito = false;
        this.mostrarGraciasModal = true;
        if (this.mesa_id) this.cargarEntregados();
      })
      .catch((err) => {
        console.error(err);
        alert('Ocurrió un error al procesar tu pedido.');
      });
    },

    // ==== Entregados & Cuenta ====
    async cargarEntregados() {
      if (!this.mesa_id) return;
      this.cargandoEntregados = true;
      try {
        const url = new URL(ENDPOINTS.entregados, window.location.origin);
        url.searchParams.set('mesa_id', this.mesa_id);
        const res = await fetch(url.toString(), {
          headers: { 'Accept': 'application/json' },
          credentials: 'same-origin',
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        const arr = Array.isArray(data) ? data : (data.pedidos || data.data || []);
        // mantenemos 2 & 3
        this.pedidosEntregados = (arr || []).filter(p => [2,3].includes(Number(p.estado)));
      } catch (e) {
        console.error(e);
        this.pedidosEntregados = [];
      } finally {
        this.cargandoEntregados = false;
      }
    },

    async pedirCuentaPedido(ordenId) {
      if (!this.mesa_id) { alert('No se encontró la mesa en la URL.'); return; }
      // Bloqueo lógico: si ya hay uno en 3, no permitir
      if (this.hayCuentaAbierta) return;

      try {
        const url = new URL(ENDPOINTS.pedirCuentaPedido, window.location.origin);
        url.searchParams.set('mesa_id', this.mesa_id);
        url.searchParams.set('orden_id', String(ordenId).replace(/\D+/g, ''));
        const res = await fetch(url.toString(), {
          method: 'GET',
          headers: { 'Accept': 'application/json' },
          credentials: 'same-origin',
        });
        if (!res.ok) {
          const txt = await res.text();
          throw new Error(`HTTP ${res.status}: ${txt.slice(0,200)}`);
        }
        // Optimista: marcar ese pedido como 3
        const idx = this.pedidosEntregados.findIndex(p => Number(p.id) === Number(ordenId));
        if (idx !== -1) this.pedidosEntregados[idx].estado = 3;
        // refrescar
        await this.cargarEntregados();
      } catch (e) {
        console.error(e);
        alert('No se pudo solicitar la cuenta de este pedido.');
      }
    },

    toast(msg) { console.log(msg); }
  };
};
</script>
