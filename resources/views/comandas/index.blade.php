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
                ... 🕓 Mesas por Activar             
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
                                    <li>+ {{ $adicion['nombre'] }} ({{ '€' . number_format($adicion['precio'], 2, ',', '.') }})</li>                                     
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
                ... 👨‍🍳 Mesas en Preparación
            </h3>

            @forelse($ordenesEnProceso as $orden)
            <div class="bg-blue-50 border-l-4 border-blue-500 shadow-md rounded-md p-4 mb-4"
                 x-data="entregaParcial({{ $orden->id }}, @js($orden->productos))">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <h4 class="text-[#153958] font-bold mb-2">
                            Mesa {{ $orden->mesa->nombre ?? 'No definida' }}
                        </h4>

                        {{-- Lista de productos con checkboxes para entrega parcial --}}
                        <div class="space-y-2 mb-4">
                            @foreach ($orden->productos as $index => $producto)
                            @php
                                $cantidadEntregada = $producto['cantidad_entregada'] ?? 0;
                                $cantidadTotal = $producto['cantidad'];
                                $cantidadPendiente = $cantidadTotal - $cantidadEntregada;
                            @endphp

                            @if($cantidadPendiente > 0)
                            <div class="flex items-center justify-between bg-white p-2 rounded border">
                                <div class="flex items-center space-x-2">
                                    <input type="checkbox"
                                           x-model="productosSeleccionados[{{ $index }}].seleccionado"
                                           :disabled="productosSeleccionados[{{ $index }}].cantidadPendiente <= 0"
                                           class="rounded text-blue-600 disabled:opacity-50">
                                    <div class="flex-1">
                                        <span class="text-sm font-medium text-[#153958]">
                                            {{ $producto['nombre'] }}
                                        </span>
                                        @if (!empty($producto['adiciones']))
                                        <ul class="text-xs text-gray-600 ml-2">
                                            @foreach ($producto['adiciones'] as $adicion)
                                            <li>+ {{ $adicion['nombre'] }}</li>
                                            @endforeach
                                        </ul>
                                        @endif
                                        <div class="text-xs text-gray-500">
                                            <span x-text="`Pendiente: ${productosSeleccionados[{{ $index }}].cantidadPendiente} de {{ $cantidadTotal }}`"></span>
                                            @if($cantidadEntregada > 0)
                                                <span>({{ $cantidadEntregada }} ya entregados)</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-2" x-show="productosSeleccionados[{{ $index }}].seleccionado && productosSeleccionados[{{ $index }}].cantidadPendiente > 0">
                                    <label class="text-xs text-gray-600">Entregar:</label>
                                    <input type="number"
                                           x-model="productosSeleccionados[{{ $index }}].cantidadEntregar"
                                           min="1"
                                           :max="productosSeleccionados[{{ $index }}].cantidadPendiente"
                                           class="w-16 text-sm border rounded px-2 py-1">
                                    <span class="text-xs text-gray-500" x-text="`de ${productosSeleccionados[{{ $index }}].cantidadPendiente}`"></span>
                                </div>
                            </div>
                            @endif
                            @endforeach
                        </div>
                    </div>
                    <span class="text-xs text-gray-500">{{ $orden->created_at->format('H:i') }}</span>
                </div>

                {{-- Botones de entrega --}}
                <div class="flex space-x-2 mt-3">
                    {{-- Entrega parcial --}}
                    <button @click="entregarSeleccionados()"
                            :disabled="!haySeleccionados()"
                            :class="haySeleccionados() ? 'bg-orange-500 hover:bg-orange-600' : 'bg-gray-400 cursor-not-allowed'"
                            class="flex-1 text-white font-bold py-2 px-3 rounded-md text-sm">
                        📦 Entregar Seleccionados
                    </button>

                    {{-- Entrega completa --}}
                    <form method="POST" action="{{ route('comandas.entregar', [$restaurante, $orden]) }}" class="flex-1 form-entregar">
                        @csrf
                        <button type="submit"
                            class="bg-blue-500 text-white font-bold py-2 px-3 rounded-md w-full hover:bg-blue-600 text-sm">
                            🍽️ Entregar Todo
                        </button>
                    </form>
                </div>
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
                ... ✅ Mesas Servidas
            </h3>

            @forelse($ordenesEntregadas as $orden)
            @php
                // Debug: verificar que $orden existe
                if (!$orden) {
                    continue;
                }

                $todoCompleto = collect($orden->productos)->every(function($producto) use ($orden) {
                    $cantidadTotal = $producto['cantidad'] ?? 1;
                    $cantidadEntregada = $producto['cantidad_entregada'] ?? ($orden->estado == 2 ? $cantidadTotal : 0);
                    return $cantidadEntregada >= $cantidadTotal;
                });
                $tieneEntregasParciales = !$todoCompleto && $orden->estado == 1;
            @endphp

            <div class="bg-green-50 border-l-4 border-[#3CB28B] shadow-md rounded-md p-4 mb-4"
                 @if($tieneEntregasParciales) x-data="entregaParcial({{ $orden->id }}, @js($orden->productos))" @endif>
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <h4 class="text-[#153958] font-bold mb-2">
                            Mesa {{ $orden->mesa->nombre ?? 'No definida' }}
                        </h4>

                        {{-- Lista de productos con estado de entrega --}}
                        <div class="space-y-2 mb-4">
                            @foreach ($orden->productos as $index => $producto)
                            @php
                                $cantidadTotal = $producto['cantidad'] ?? 1;
                                $cantidadEntregada = $producto['cantidad_entregada'] ?? ($orden->estado == 2 ? $cantidadTotal : 0);
                                $cantidadPendiente = $cantidadTotal - $cantidadEntregada;
                                $productoEntregado = $cantidadEntregada >= $cantidadTotal;
                            @endphp

                            <div class="flex items-center justify-between bg-white p-2 rounded border">
                                {{-- Checkbox para entregas parciales adicionales si aún hay productos pendientes --}}
                                @if($tieneEntregasParciales && $cantidadPendiente > 0)
                                <div class="flex items-center space-x-2 flex-1">
                                    <input type="checkbox"
                                           x-model="productosSeleccionados[{{ $index }}].seleccionado"
                                           class="rounded text-blue-600">
                                    <div class="flex-1">
                                        <span class="text-sm font-medium text-[#153958]">
                                            {{ $producto['nombre'] }}
                                        </span>
                                        @if (!empty($producto['adiciones']))
                                        <ul class="text-xs text-gray-600 ml-2">
                                            @foreach ($producto['adiciones'] as $adicion)
                                            <li>+ {{ $adicion['nombre'] }}</li>
                                            @endforeach
                                        </ul>
                                        @endif
                                        <div class="text-xs text-gray-500">
                                            Entregado: {{ $cantidadEntregada }}/{{ $cantidadTotal }}
                                            @if($cantidadPendiente > 0)
                                                - Pendiente: {{ $cantidadPendiente }}
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Input para cantidad adicional a entregar --}}
                                    <div class="flex items-center space-x-2" x-show="productosSeleccionados[{{ $index }}].seleccionado">
                                        <label class="text-xs text-gray-600">Entregar:</label>
                                        <input type="number"
                                               x-model="productosSeleccionados[{{ $index }}].cantidadEntregar"
                                               min="1"
                                               max="{{ $cantidadPendiente }}"
                                               class="w-16 text-sm border rounded px-2 py-1">
                                        <span class="text-xs text-gray-500">de {{ $cantidadPendiente }}</span>
                                    </div>
                                </div>
                                @else
                                <div class="flex items-center space-x-2 flex-1">
                                    <div class="flex-shrink-0">
                                        @if($productoEntregado)
                                            <span class="w-4 h-4 bg-green-500 rounded-full flex items-center justify-center">
                                                <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        @else
                                            <span class="w-4 h-4 bg-orange-500 rounded-full flex items-center justify-center">
                                                <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <span class="text-sm font-medium text-[#153958]">
                                            {{ $producto['nombre'] }}
                                        </span>
                                        @if (!empty($producto['adiciones']))
                                        <ul class="text-xs text-gray-600 ml-2">
                                            @foreach ($producto['adiciones'] as $adicion)
                                            <li>+ {{ $adicion['nombre'] }}</li>
                                            @endforeach
                                        </ul>
                                        @endif
                                    </div>

                                    <div class="text-right">
                                        @if($productoEntregado)
                                            <span class="text-xs font-medium text-green-600">
                                                ✅ {{ $cantidadTotal }}x Entregado
                                            </span>
                                        @else
                                            <span class="text-xs font-medium text-orange-600">
                                                📦 {{ $cantidadEntregada }}/{{ $cantidadTotal }} Entregado
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <span class="text-xs text-gray-500">{{ $orden->created_at->format('H:i') }}</span>
                </div>

                {{-- Botones de acción --}}
                <div class="flex justify-between items-center mt-3 pt-3 border-t border-green-200">
                    <p class="text-sm text-[#3CB28B] font-semibold">
                        Total: €{{ number_format($orden->total, 0, ',', '.') }}
                    </p>

                    <div class="flex items-center space-x-2">
                        <span class="text-xs px-2 py-1 rounded {{ $todoCompleto ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800' }}">
                            {{ $todoCompleto ? '✅ Completamente entregado' : '📦 Entrega parcial' }}
                        </span>

                        {{-- Botones adicionales para entregas parciales --}}
                        @if($tieneEntregasParciales)
                        <div class="flex space-x-2">
                            <button @click="entregarSeleccionados()"
                                    :disabled="!haySeleccionados()"
                                    :class="haySeleccionados() ? 'bg-orange-500 hover:bg-orange-600' : 'bg-gray-400 cursor-not-allowed'"
                                    class="text-white font-bold py-1 px-2 rounded text-xs">
                                📦 Entregar +
                            </button>

                            <form method="POST" action="{{ route('comandas.entregar', [$restaurante, $orden]) }}" class="form-entregar">
                                @csrf
                                <button type="submit"
                                    class="bg-green-500 text-white font-bold py-1 px-2 rounded text-xs hover:bg-green-600">
                                    🍽️ Completar
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>
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

{{-- ==== ALPINE.JS COMPONENT ====  --}}
<script>
// Componente Alpine.js para entrega parcial
function entregaParcial(ordenId, productos) {
    return {
        ordenId: ordenId,
        productos: productos || [],
        productosSeleccionados: [],

        init() {
            this.actualizarProductos();
        },

        actualizarProductos() {
            // Inicializar/actualizar estado de productos
            this.productosSeleccionados = this.productos.map((producto, index) => {
                const cantidadPendiente = (producto.cantidad || 1) - (producto.cantidad_entregada || 0);
                return {
                    indice: index,
                    seleccionado: false,
                    cantidadEntregar: Math.min(1, cantidadPendiente), // No puede entregar más de lo pendiente
                    cantidadPendiente: cantidadPendiente,
                    producto: producto
                };
            });
            console.log('Productos actualizados:', this.productosSeleccionados);
        },

        haySeleccionados() {
            return this.productosSeleccionados.some(p =>
                p.seleccionado &&
                p.cantidadEntregar > 0 &&
                p.cantidadPendiente > 0 &&
                p.cantidadEntregar <= p.cantidadPendiente
            );
        },

        async entregarSeleccionados() {
            console.log('Productos seleccionados state:', this.productosSeleccionados);

            const seleccionados = this.productosSeleccionados
                .filter(p => p.seleccionado && p.cantidadEntregar > 0)
                .map(p => ({
                    indice: p.indice,
                    cantidad: p.cantidadEntregar,
                    producto: p.producto
                }));

            console.log('Datos que se van a enviar:', seleccionados);

            if (seleccionados.length === 0) {
                alert('Selecciona al menos un producto para entregar.');
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const url = `{{ route('comandas.entregar', [$restaurante, '__ORDEN_ID__']) }}`.replace('__ORDEN_ID__', this.ordenId);

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        entrega_parcial: true,
                        productos_entregar: seleccionados
                    })
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Response error:', {
                        status: response.status,
                        statusText: response.statusText,
                        body: errorText
                    });
                    throw new Error(`HTTP ${response.status}: ${errorText}`);
                }

                const data = await response.json();

                if (data.success !== false) {
                    // Refrescar el panel
                    if (window.refrescarPanel) {
                        await window.refrescarPanel();
                    } else {
                        window.location.reload();
                    }
                } else {
                    alert(data.message || 'Error al procesar la entrega parcial.');
                }
            } catch (error) {
                console.error('Error en entrega parcial:', error);
                alert('Error al procesar la entrega parcial. Intenta nuevamente.');
            }
        }
    };
}
</script>

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
                // Guardar el estado de las entregas parciales antes de reemplazar
                const estadosEntrega = {};
                actual.querySelectorAll('[x-data*="entregaParcial"]').forEach(el => {
                    const ordenId = el._x_dataStack?.[0]?.ordenId;
                    if (ordenId && el._x_dataStack?.[0]?.productosSeleccionados) {
                        estadosEntrega[ordenId] = {
                            productosSeleccionados: JSON.parse(JSON.stringify(el._x_dataStack[0].productosSeleccionados))
                        };
                    }
                });

                actual.replaceWith(nuevoGrid);
                wireUpActions();

                // Actualizar los componentes Alpine con datos frescos después del reemplazo
                setTimeout(() => {
                    nuevoGrid.querySelectorAll('[x-data*="entregaParcial"]').forEach(el => {
                        const alpineData = el._x_dataStack?.[0];
                        if (alpineData && alpineData.actualizarProductos) {
                            // Actualizar productos con datos frescos del DOM
                            alpineData.actualizarProductos();
                        }
                    });
                }, 100);
            }
        } catch (e) { console.error('Error refrescando panel:', e); }
    }

    // Hacer refrescarPanel accesible globalmente
    window.refrescarPanel = refrescarPanel;    

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
