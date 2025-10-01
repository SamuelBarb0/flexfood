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
        'mesas'              => $mesasConEstado,
        'tieneRestaurante'   => !empty($restaurante),
        'tieneDatos'         => $mesasConEstado->isNotEmpty() || $categorias->isNotEmpty(),
        'menuPublicoUrl'     => $restaurante ? route('menu.publico', ['restaurante' => $restaurante->slug]) : null,
        'enviarPedidoUrl'    => $restaurante ? route('comandas.store', ['restaurante' => $restaurante->slug]) : null,
    ];
@endphp

<div
    class="py-6 px-4 sm:px-6 lg:px-8 bg-gray-100 min-h-screen"
    x-data='dashboardTpv(@json($dashboardOpts))'
>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Dashboard de Estado</h2>
    </div>

    {{-- ===================== PANEL (PARCIAL) ===================== --}}
    @section('__panel_estado')
    <div id="panel-estado">

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
            {{-- Botones de gestión --}}
            <div class="mb-4 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Estado de las Mesas</h3>
                <div class="flex gap-2">
                    <button @click="toggleModoFusion()"
                            :class="modoFusion ? 'bg-red-600 hover:bg-red-700' : 'bg-purple-600 hover:bg-purple-700'"
                            class="text-white px-4 py-2 rounded-lg text-sm transition-colors">
                        <span x-text="modoFusion ? 'Cancelar Fusión' : '🔗 Fusionar Mesas'"></span>
                    </button>
                    <button @click="mostrarGestionZonas = true"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
                        Gestionar Zonas
                    </button>
                </div>
            </div>

            {{-- Panel de fusión de mesas --}}
            <div x-show="modoFusion" class="mb-4 bg-purple-50 border-2 border-purple-300 rounded-lg p-4"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100">
                <div class="flex justify-between items-center mb-3">
                    <div>
                        <h4 class="text-md font-bold text-purple-800">Modo Fusión de Mesas</h4>
                        <p class="text-sm text-purple-600">
                            Selecciona mesas para fusionarlas (mínimo 2). La primera será la principal.
                        </p>
                    </div>
                    <button @click="limpiarSeleccionFusion()"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-1 rounded text-sm">
                        Limpiar
                    </button>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-sm text-purple-700">
                        <strong x-text="mesasSeleccionadasFusion.length"></strong> mesa(s) seleccionada(s)
                        <span x-show="mesasSeleccionadasFusion.length > 0" class="ml-2">
                            [ <span x-text="mesasSeleccionadasFusion.map(m => 'Mesa ' + m.numero).join(', ')"></span> ]
                        </span>
                    </div>
                    <button @click="confirmarFusion()"
                            x-show="mesasSeleccionadasFusion.length >= 2"
                            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        ✓ Confirmar Fusión
                    </button>
                </div>
            </div>

            {{-- Mesas agrupadas por zonas --}}
            @php
                $mesasPorZona = $mesasConEstado->groupBy('zona_id');
                $mesasSinZona = $mesasPorZona->get(null, collect());
                $zonasConMesas = $zonas->filter(function($zona) use ($mesasPorZona) {
                    return $mesasPorZona->has($zona->id);
                });
            @endphp

            {{-- Zonas con mesas --}}
            @foreach ($zonasConMesas as $zona)
                <div class="mb-6">
                    <h4 class="text-md font-semibold text-gray-700 mb-3 border-b border-gray-200 pb-2">
                        {{ $zona->nombre }}
                        @if($zona->descripcion)
                            <span class="text-sm text-gray-500 font-normal">- {{ $zona->descripcion }}</span>
                        @endif
                    </h4>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6 gap-4">
                        @foreach ($mesasPorZona->get($zona->id, collect()) as $mesa)
                            @php
                                switch ((int)($mesa['estado'] ?? 0)) {
                                    case 1: $estadoTexto = 'Activa';        $bg = 'bg-green-500 text-white'; break;
                                    case 2: $estadoTexto = 'Ocupada';       $bg = 'bg-blue-500 text-white';  break;
                                    case 3: $estadoTexto = 'Pide la cuenta';$bg = 'bg-orange-500 text-white';break;
                                    default:$estadoTexto = 'Libre';         $bg = 'bg-gray-300 text-gray-800';break;
                                }
                            @endphp

                            <div class="relative {{ $bg }} rounded-lg p-4 text-center shadow-sm cursor-pointer transition-all"
                                 :class="{'ring-4 ring-purple-500': modoFusion && esMesaSeleccionada(@js($mesa['id'] ?? ($mesa['mesa_id'] ?? null)))}"
                                 @click="modoFusion ? toggleSeleccionMesa(@js($mesa['id'] ?? ($mesa['mesa_id'] ?? null)), @js($mesa['numero'] ?? null)) : clickMesa(
                                     @js($mesa['numero'] ?? null),
                                     @js($estadoTexto),
                                     @js($mesa['cuenta'] ?? []),
                                     @js($mesa['orden_id'] ?? null),
                                     @js($mesa['id'] ?? ($mesa['mesa_id'] ?? null))
                                 )">

                                {{-- Indicador de mesa fusionada --}}
                                @if(!empty($mesa['fusionada']))
                                    <div class="absolute top-1 right-1 bg-purple-600 rounded-full px-2 py-0.5 text-xs font-bold text-white shadow-lg animate-pulse"
                                         title="Mesa fusionada con: {{ implode(', ', $mesa['mesas_grupo'] ?? []) }}">
                                        🔗
                                    </div>
                                    {{-- Banda lateral morada para mayor visibilidad --}}
                                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-purple-600 rounded-l-lg"></div>
                                @endif

                                {{-- Checkbox para modo fusión --}}
                                <div x-show="modoFusion" class="absolute top-1 left-1">
                                    <input type="checkbox"
                                           :checked="esMesaSeleccionada(@js($mesa['id'] ?? ($mesa['mesa_id'] ?? null)))"
                                           class="w-5 h-5 rounded border-2 border-white"
                                           @click.stop="toggleSeleccionMesa(@js($mesa['id'] ?? ($mesa['mesa_id'] ?? null)), @js($mesa['numero'] ?? null))">
                                </div>

                                <div class="text-2xl font-bold">{{ $mesa['numero'] ?? '-' }}</div>

                                {{-- Info adicional de fusión --}}
                                @if(!empty($mesa['fusionada']) && !empty($mesa['mesas_grupo']))
                                    <div class="text-[10px] text-white bg-purple-500/30 rounded px-1 mb-1">
                                        Grupo: {{ implode(', ', array_map(fn($n) => "M$n", $mesa['mesas_grupo'])) }}
                                    </div>
                                @endif

                                <div class="text-sm font-semibold mb-1 capitalize">{{ $estadoTexto }}</div>
                                <div class="text-sm">{{ $mesa['tiempo'] ?? '-' }}</div>
                                <div class="text-md font-bold mt-1">
                                    {{ (($mesa['total'] ?? 0) > 0) ? number_format($mesa['total'], 2, ',', '.') . ' €' : '- €' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            {{-- Mesas sin zona asignada --}}
            @if ($mesasSinZona->isNotEmpty())
                <div class="mb-6">
                    <h4 class="text-md font-semibold text-gray-700 mb-3 border-b border-gray-200 pb-2">
                        Sin zona asignada
                    </h4>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6 gap-4">
                        @foreach ($mesasSinZona as $mesa)
                            @php
                                switch ((int)($mesa['estado'] ?? 0)) {
                                    case 1: $estadoTexto = 'Activa';        $bg = 'bg-green-500 text-white'; break;
                                    case 2: $estadoTexto = 'Ocupada';       $bg = 'bg-blue-500 text-white';  break;
                                    case 3: $estadoTexto = 'Pide la cuenta';$bg = 'bg-orange-500 text-white';break;
                                    default:$estadoTexto = 'Libre';         $bg = 'bg-gray-300 text-gray-800';break;
                                }
                            @endphp

                            <div class="relative {{ $bg }} rounded-lg p-4 text-center shadow-sm cursor-pointer transition-all"
                                 :class="{'ring-4 ring-purple-500': modoFusion && esMesaSeleccionada(@js($mesa['id'] ?? ($mesa['mesa_id'] ?? null)))}"
                                 @click="modoFusion ? toggleSeleccionMesa(@js($mesa['id'] ?? ($mesa['mesa_id'] ?? null)), @js($mesa['numero'] ?? null)) : clickMesa(
                                     @js($mesa['numero'] ?? null),
                                     @js($estadoTexto),
                                     @js($mesa['cuenta'] ?? []),
                                     @js($mesa['orden_id'] ?? null),
                                     @js($mesa['id'] ?? ($mesa['mesa_id'] ?? null))
                                 )">

                                {{-- Indicador de mesa fusionada --}}
                                @if(!empty($mesa['fusionada']))
                                    <div class="absolute top-1 right-1 bg-purple-600 rounded-full px-2 py-0.5 text-xs font-bold text-white shadow-lg animate-pulse"
                                         title="Mesa fusionada con: {{ implode(', ', $mesa['mesas_grupo'] ?? []) }}">
                                        🔗
                                    </div>
                                    {{-- Banda lateral morada para mayor visibilidad --}}
                                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-purple-600 rounded-l-lg"></div>
                                @endif

                                {{-- Checkbox para modo fusión --}}
                                <div x-show="modoFusion" class="absolute top-1 left-1">
                                    <input type="checkbox"
                                           :checked="esMesaSeleccionada(@js($mesa['id'] ?? ($mesa['mesa_id'] ?? null)))"
                                           class="w-5 h-5 rounded border-2 border-white"
                                           @click.stop="toggleSeleccionMesa(@js($mesa['id'] ?? ($mesa['mesa_id'] ?? null)), @js($mesa['numero'] ?? null))">
                                </div>

                                <div class="text-2xl font-bold">{{ $mesa['numero'] ?? '-' }}</div>

                                {{-- Info adicional de fusión --}}
                                @if(!empty($mesa['fusionada']) && !empty($mesa['mesas_grupo']))
                                    <div class="text-[10px] text-white bg-purple-500/30 rounded px-1 mb-1">
                                        Grupo: {{ implode(', ', array_map(fn($n) => "M$n", $mesa['mesas_grupo'])) }}
                                    </div>
                                @endif

                                <div class="text-sm font-semibold mb-1 capitalize">{{ $estadoTexto }}</div>
                                <div class="text-sm">{{ $mesa['tiempo'] ?? '-' }}</div>
                                <div class="text-md font-bold mt-1">
                                    {{ (($mesa['total'] ?? 0) > 0) ? number_format($mesa['total'], 2, ',', '.') . ' €' : '- €' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Estado cuando no hay mesas --}}
            @if ($mesasConEstado->isEmpty())
                <div class="text-gray-500 text-center py-6">
                    <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <p class="font-medium">No hay mesas disponibles</p>
                    <p class="text-sm">Agrega mesas para comenzar.</p>
                </div>
            @endif

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
        @endif

    </div>
    @show
    {{-- ===================== /PANEL (PARCIAL) ===================== --}}

    @include('partials.modal-tpv')

    {{-- Modal Gestión de Zonas --}}
    <div x-show="mostrarGestionZonas"
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="bg-white rounded-lg w-full max-w-4xl p-6 relative max-h-[90vh] overflow-y-auto"
             @click.away="mostrarGestionZonas = false"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            <button @click="mostrarGestionZonas = false" class="absolute top-3 right-4 text-gray-500 text-xl">×</button>

            <h2 class="text-xl font-bold mb-4">Gestión de Zonas</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Lista de zonas existentes --}}
                <div>
                    <h3 class="font-semibold text-gray-700 mb-3">Zonas Existentes</h3>
                    <div class="space-y-3 max-h-64 overflow-y-auto">
                        <template x-for="zona in zonas" :key="zona.id">
                            <div class="border rounded p-3 bg-gray-50">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="font-medium" x-text="zona.nombre"></div>
                                        <div class="text-sm text-gray-500" x-text="zona.descripcion || 'Sin descripción'"></div>
                                        <div class="text-xs text-gray-400">Orden: <span x-text="zona.orden"></span></div>
                                    </div>
                                    <div class="flex space-x-1 ml-2">
                                        <button @click="abrirAsignacionMesas(zona)"
                                                class="text-green-600 hover:text-green-800 text-sm"
                                                title="Asignar mesas">
                                            📋
                                        </button>
                                        <button @click="editarZona(zona)"
                                                class="text-blue-600 hover:text-blue-800 text-sm"
                                                title="Editar zona">
                                            ✏️
                                        </button>
                                        <button @click="eliminarZona(zona)"
                                                class="text-red-600 hover:text-red-800 text-sm"
                                                title="Eliminar zona">
                                            🗑️
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div x-show="zonas.length === 0" class="text-gray-500 text-center py-4 italic">
                            No hay zonas creadas aún
                        </div>
                    </div>
                </div>

                {{-- Formulario para crear/editar zona --}}
                <div>
                    <h3 class="font-semibold text-gray-700 mb-3" x-text="modoEdicion ? 'Editar Zona' : 'Crear Nueva Zona'"></h3>

                    <div x-show="!modoEdicion">
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                                <input type="text"
                                       x-model="nuevaZona.nombre"
                                       placeholder="Ej: Terraza, Salón Principal, Sótano..."
                                       class="w-full px-3 py-2 border rounded text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                                <textarea x-model="nuevaZona.descripcion"
                                          placeholder="Descripción opcional de la zona..."
                                          rows="2"
                                          class="w-full px-3 py-2 border rounded text-sm"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                                <input type="number"
                                       x-model="nuevaZona.orden"
                                       min="0"
                                       class="w-full px-3 py-2 border rounded text-sm">
                            </div>
                        </div>

                        <div class="mt-4 flex space-x-2">
                            <button @click="crearZona()"
                                    class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">
                                Crear Zona
                            </button>
                        </div>
                    </div>

                    <div x-show="modoEdicion" x-cloak>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                                <input type="text"
                                       x-model="zonaEditar.nombre"
                                       class="w-full px-3 py-2 border rounded text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                                <textarea x-model="zonaEditar.descripcion"
                                          rows="2"
                                          class="w-full px-3 py-2 border rounded text-sm"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                                <input type="number"
                                       x-model="zonaEditar.orden"
                                       min="0"
                                       class="w-full px-3 py-2 border rounded text-sm">
                            </div>
                        </div>

                        <div class="mt-4 flex space-x-2">
                            <button @click="actualizarZona()"
                                    class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
                                Actualizar
                            </button>
                            <button @click="cancelarEdicion()"
                                    class="bg-gray-300 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-400">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t">
                <button @click="mostrarGestionZonas = false"
                        class="bg-gray-200 px-4 py-2 rounded text-gray-700 text-sm">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Asignación de Mesas --}}
    <div x-show="zonaAsignacion"
         class="fixed inset-0 bg-black/50 z-[70] flex items-center justify-center"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="bg-white rounded-lg w-full max-w-2xl p-6 relative max-h-[80vh] overflow-y-auto"
             @click.away="cerrarAsignacionMesas()"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            <button @click="cerrarAsignacionMesas()" class="absolute top-3 right-4 text-gray-500 text-xl">×</button>

            <h2 class="text-xl font-bold mb-4">
                Asignar Mesas a: <span x-text="zonaAsignacion?.nombre" class="text-blue-600"></span>
            </h2>

            <p class="text-sm text-gray-600 mb-4">
                Selecciona las mesas que deseas asignar a esta zona. Las mesas ya asignadas aparecen marcadas.
            </p>

            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 mb-6">
                <template x-for="mesa in mesasDisponibles" :key="mesa.id">
                    <div class="relative">
                        <input type="checkbox"
                               :id="'mesa-' + mesa.id"
                               :value="mesa.id"
                               @change="toggleMesaSeleccion(mesa.id)"
                               :checked="mesasSeleccionadas.includes(mesa.id)"
                               class="absolute opacity-0 peer">
                        <label :for="'mesa-' + mesa.id"
                               class="flex flex-col items-center justify-center p-3 border-2 rounded-lg cursor-pointer transition-all
                                      peer-checked:border-blue-500 peer-checked:bg-blue-50
                                      hover:border-gray-400 border-gray-200 bg-white">
                            <div class="text-lg font-bold" x-text="mesa.nombre"></div>
                            <div class="text-xs text-gray-500">
                                <span x-show="mesa.zona_id && mesa.zona_id !== zonaAsignacion?.id"
                                      x-text="'En otra zona'"
                                      class="text-orange-600"></span>
                                <span x-show="mesa.zona_id === zonaAsignacion?.id"
                                      class="text-green-600">En esta zona</span>
                                <span x-show="!mesa.zona_id"
                                      class="text-gray-400">Sin zona</span>
                            </div>
                        </label>
                        <div x-show="mesasSeleccionadas.includes(mesa.id)"
                             class="absolute -top-1 -right-1 w-4 h-4 bg-blue-500 rounded-full flex items-center justify-center">
                            <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex justify-between items-center pt-4 border-t">
                <div class="text-sm text-gray-600">
                    <span x-text="mesasSeleccionadas.length"></span> mesa(s) seleccionada(s)
                </div>
                <div class="flex space-x-2">
                    <button @click="cerrarAsignacionMesas()"
                            class="bg-gray-300 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-400">
                        Cancelar
                    </button>
                    <button @click="asignarMesasAZona()"
                            class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
                        Asignar Mesas
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('partials.modal-ticket')
</div>
@endsection


{{-- ==== LIVE REFRESH (Polling + AJAX en formularios) ==== --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const basePanel = "{{ route('rest.dashboard', ['restaurante' => $restaurante?->slug]) }}";

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

      const nuevo = tmp.querySelector('#panel-estado') || tmp.firstElementChild;
      const actual = document.querySelector('#panel-estado');
      if (!nuevo || !actual) return;

      if (actual.innerHTML.trim() !== nuevo.innerHTML.trim()) {
        actual.replaceWith(nuevo);
        wireUpActions(); // re-vincula eventos si los hubiera
      }
    } catch (e) {
      console.error('Error refrescando panel:', e);
    }
  }

  // Si agregas formularios dentro del panel, márcalos con data-ajax="true"
  function wireUpActions() {
    document.querySelectorAll('form[data-ajax="true"]').forEach((form) => {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const url = form.getAttribute('action') || form.dataset.url;
        const method = (form.getAttribute('method') || 'POST').toUpperCase();

        try {
          const res = await fetch(url, {
            method,
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
              'X-CSRF-TOKEN': csrfToken,
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: new FormData(form)
          });
          if (!res.ok) throw new Error('Error en la acción AJAX');
          await refrescarPanel();
        } catch (err) {
          console.error(err);
          alert('Ocurrió un problema al procesar la acción.');
        }
      }, { once: true });
    });
  }

  // Intervalo de refresco sincronizado con comandas (cada 6 segundos)
  setInterval(refrescarPanel, 6000);
  wireUpActions();

  // También refrescar cuando regresamos de comandas o hay cambios
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
      setTimeout(refrescarPanel, 500);
    }
  });
});
</script>

<script>
function dashboardTpv(opts = {}) {
  const ENDPOINTS = {
    finalizar: opts.finalizarUrl || null,
    ticketEmailBase: opts.ticketEmailBase || '',
    menuPublico: opts.menuPublicoUrl || null,
    enviarPedido: opts.enviarPedidoUrl || null,
  };

  return {
    // estado UI
    mostrarModal: false,
    mostrarTicket: false,
    mostrarDetalleProducto: false,
    mostrarModalTraspasar: false,
    mesaDestinoId: '',
    mesaSeleccionada: null,
    estadoMesa: '',
    emailCliente: '',
    emailDestino: '',
    cuentaActual: [],
    ticketActual: null,
    categorias: opts.categorias || [],
    mesas: opts.mesas || [],
    busqueda: '',
    ordenIdSeleccionada: null,
    productoSeleccionado: null,
    adicionesSeleccionadas: [],

    // contexto
    tieneRestaurante: !!opts.tieneRestaurante,
    tieneDatos: !!opts.tieneDatos,
    restauranteNombre: opts.restauranteNombre || null,

    // Debug
    init() {
      console.log('Categorías cargadas:', this.categorias);
      if (this.categorias && this.categorias.length > 0) {
        console.log('Primera categoría con productos:', this.categorias[0]);
        if (this.categorias[0].productos && this.categorias[0].productos.length > 0) {
          console.log('Primer producto con adiciones:', this.categorias[0].productos[0]);
        }
      }
    },

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

    // Agrupar productos por mesa de origen
    get productosPorMesa() {
      if (!this.cuentaActual || this.cuentaActual.length === 0) {
        return [];
      }

      // Verificar si hay productos con mesa_origen
      const tieneMesas = this.cuentaActual.some(item => item.mesa_origen);

      if (!tieneMesas) {
        return [{ mesa: null, productos: this.cuentaActual }];
      }

      // Agrupar por mesa_origen
      const grupos = {};
      this.cuentaActual.forEach(item => {
        const mesa = item.mesa_origen || 'Sin mesa';
        if (!grupos[mesa]) {
          grupos[mesa] = [];
        }
        grupos[mesa].push(item);
      });

      return Object.entries(grupos).map(([mesa, productos]) => ({
        mesa,
        productos
      }));
    },

    /**
     * Siempre abre el modal TPV para gestionar la mesa.
     */
    async clickMesa(numero, estado, cuenta = [], ordenId = null, mesaId = null) {
      if (!this.tieneRestaurante) {
        alert('No hay restaurante asignado.');
        return;
      }

      console.log('Estado recibido:', estado, 'Orden ID:', ordenId);
      this.mesaSeleccionada = { numero, id: mesaId };
      this.estadoMesa = estado;
      this.ordenIdSeleccionada = ordenId;

      // Para mesas libres, inicializar cuenta vacía
      if (estado === 'Libre') {
        this.cuentaActual = [];
      } else {
        // Para mesas ocupadas, SIEMPRE obtener datos más frescos si hay ordenId
        if (ordenId) {
          try {
            const response = await fetch(`/r/{{ $restaurante?->slug }}/ordenes/${ordenId}/datos-frescos`, {
              credentials: 'same-origin',
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache'
              }
            });

            if (response.ok) {
              const data = await response.json();
              console.log('Datos frescos obtenidos:', data.productos);

this.cuentaActual = (data.productos || []).map(i => ({
  id: i.id || i.producto_id || null,
  nombre: i.nombre,
  precio_base: parseFloat(i.precio_base ?? i.precio ?? 0) || 0,
  precio:      parseFloat(i.precio_base ?? i.precio ?? 0) || 0,
  cantidad:            parseFloat(i.cantidad ?? 1) || 0,           // 👈
  cantidad_entregada:  parseFloat(i.cantidad_entregada ?? 0) || 0, // 👈
  mesa_origen: i.mesa_origen || null,
  adiciones: i.adiciones ?? []
}));

              console.log('Cuenta actualizada con datos frescos:', this.cuentaActual);
            } else {
              console.warn('Error en respuesta de datos frescos, usando datos locales');
              this.cargarCuentaLocal(cuenta);
            }
          } catch (error) {
            console.error('Error obteniendo datos frescos:', error);
            this.cargarCuentaLocal(cuenta);
          }
        } else {
          this.cargarCuentaLocal(cuenta);
        }
      }

      this.mostrarModal = true;
    },

    cargarCuentaLocal(cuenta) {
this.cuentaActual = (cuenta || []).map(i => ({
  id: i.id || i.producto_id || null,
  nombre: i.nombre,
  precio_base: parseFloat(i.precio_base ?? i.precio ?? 0) || 0,
  precio:      parseFloat(i.precio_base ?? i.precio ?? 0) || 0,
  cantidad:            parseFloat(i.cantidad ?? 1) || 0,           // 👈
  cantidad_entregada:  parseFloat(i.cantidad_entregada ?? 0) || 0, // 👈
  mesa_origen: i.mesa_origen || null,
  adiciones: i.adiciones ?? []
}));
    },

    async refrescarCuentaActual() {
      if (!this.ordenIdSeleccionada) {
        console.log('No hay orden seleccionada para refrescar');
        return;
      }

      try {
        const response = await fetch(`/r/{{ $restaurante?->slug }}/ordenes/${this.ordenIdSeleccionada}/datos-frescos`, {
          credentials: 'same-origin',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Cache-Control': 'no-cache'
          }
        });

        if (response.ok) {
          const data = await response.json();
          console.log('Cuenta refrescada manualmente:', data.productos);

          this.cuentaActual = (data.productos || []).map(i => ({
            id: i.id || i.producto_id || null,
            nombre: i.nombre,
            precio_base: parseFloat(i.precio_base ?? i.precio ?? 0) || 0,
            precio: parseFloat(i.precio_base ?? i.precio ?? 0) || 0,
            cantidad: i.cantidad ?? 1,
            cantidad_entregada: i.cantidad_entregada ?? 0,
            mesa_origen: i.mesa_origen || null,
            adiciones: i.adiciones ?? []
          }));

          // Mostrar feedback visual
          const btn = document.querySelector('[\\@click="refrescarCuentaActual()"]');
          if (btn) {
            btn.classList.add('bg-green-500');
            btn.classList.remove('bg-blue-500');
            setTimeout(() => {
              btn.classList.remove('bg-green-500');
              btn.classList.add('bg-blue-500');
            }, 1000);
          }
        } else {
          console.error('Error al refrescar cuenta:', response.status);
          alert('Error al actualizar la cuenta');
        }
      } catch (error) {
        console.error('Error refrescando cuenta:', error);
        alert('Error de conexión al actualizar');
      }
    },

    abrirDetalleProducto(producto) {
      console.log('Producto seleccionado:', producto);
      console.log('Adiciones del producto:', producto.adiciones);
      this.productoSeleccionado = { ...producto };
      this.adicionesSeleccionadas = [];
      this.mostrarDetalleProducto = true;
    },

    cerrarDetalleProducto() {
      this.mostrarDetalleProducto = false;
      this.productoSeleccionado = null;
      this.adicionesSeleccionadas = [];
    },

    toggleAdicion(adicion) {
      const index = this.adicionesSeleccionadas.findIndex(a => a.id === adicion.id);
      if (index >= 0) {
        this.adicionesSeleccionadas.splice(index, 1);
      } else {
        // Asegurar que el precio sea numérico
        this.adicionesSeleccionadas.push({
          ...adicion,
          precio: parseFloat(adicion.precio) || 0
        });
      }
    },

    calcularPrecioConAdiciones() {
      if (!this.productoSeleccionado) return 0;
      const base = parseFloat(this.productoSeleccionado.precio) || 0;
      const adiciones = this.adicionesSeleccionadas.reduce((sum, a) => sum + (parseFloat(a.precio) || 0), 0);
      return base + adiciones;
    },

    agregarProductoConAdiciones() {
      if (!this.productoSeleccionado) return;

      const existente = (this.cuentaActual || []).find(i =>
        i.id === this.productoSeleccionado.id &&
        JSON.stringify(i.adiciones ?? []) === JSON.stringify(this.adicionesSeleccionadas ?? [])
      );

      if (existente) {
        existente.cantidad += 1;
      } else {
        this.cuentaActual.push({
          id: this.productoSeleccionado.id,
          nombre: this.productoSeleccionado.nombre,
          precio_base: parseFloat(this.productoSeleccionado.precio),
          precio: parseFloat(this.productoSeleccionado.precio),
          cantidad: 1,
          adiciones: [...this.adicionesSeleccionadas]
        });
      }

      this.cerrarDetalleProducto();
    },

    agregarProducto(producto) {
      // Función legacy para productos sin adiciones
      const existente = (this.cuentaActual || []).find(i =>
        i.id === producto.id &&
        JSON.stringify(i.adiciones ?? []) === JSON.stringify([])
      );
      if (existente) { existente.cantidad += 1; return; }
      this.cuentaActual.push({
        id: producto.id,
        nombre: producto.nombre,
        precio_base: parseFloat(producto.precio),
        precio:      parseFloat(producto.precio),
        cantidad: 1,
        adiciones: []
      });
    },

    cerrarMesa() {
      if (!this.tieneRestaurante) { alert('No hay restaurante asignado.'); return; }
      if (!ENDPOINTS.finalizar) { alert('No se pudo determinar el endpoint de finalización.'); return; }
      const mesaId = this.ticketActual?.mesa_id || this.mesaSeleccionada?.id;
      if (!mesaId) { alert('No se encontró el ID de la mesa.'); return; }

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

    async gestionarTicket() {
      this.mostrarModal = false;

      // Si hay orden, obtener datos del ticket desde el servidor (incluye info de fusión)
      if (this.ordenIdSeleccionada) {
        try {
          const response = await fetch(`/r/{{ $restaurante?->slug }}/ordenes/${this.ordenIdSeleccionada}/ticket`, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          });

          if (response.ok) {
            const data = await response.json();
            this.ticketActual = {
              id: this.ordenIdSeleccionada,
              restaurante_nombre: this.restauranteNombre || '',
              mesa: this.mesaSeleccionada?.numero,
              mesa_id: data.mesa,
              mesas_info: data.mesas_info || null,
              fusionada: data.fusionada || false,
              productos_por_mesa: data.productos_por_mesa || null,
              fecha: data.fecha,
              productos: data.productos || [],
              total: data.total
            };
            this.mostrarTicket = true;
            return;
          }
        } catch (error) {
          console.error('Error al obtener ticket:', error);
        }
      }

      // Fallback: usar datos locales
      this.ticketActual = {
        id: this.ordenIdSeleccionada ?? null,
        restaurante_nombre: this.restauranteNombre || '',
        mesa: this.mesaSeleccionada?.numero,
        mesa_id: this.mesaSeleccionada?.id,
        mesas_info: null,
        fusionada: false,
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
      if (!this.ticketActual?.id) { alert('No se encontró el ID de la orden.'); return; }
      if (!this.emailDestino || !/.+@.+\..+/.test(this.emailDestino)) { alert('Correo inválido.'); return; }
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
      .catch(() => alert('Ocurrió un error al enviar el correo.'));
    },

    enviarPedido() {
      if (!this.tieneRestaurante) {
        alert('No hay restaurante asignado.');
        return;
      }

      if (!ENDPOINTS.enviarPedido) {
        alert('No se pudo determinar el endpoint para enviar pedidos.');
        return;
      }

      if (!this.cuentaActual || this.cuentaActual.length === 0) {
        alert('No hay productos en el pedido.');
        return;
      }

      if (!this.mesaSeleccionada?.id) {
        alert('No se pudo identificar la mesa.');
        return;
      }

      // Convertir cuentaActual al formato que espera el backend
      const carrito = this.cuentaActual.map(item => ({
        id: item.id || null,
        nombre: item.nombre,
        precio_base: item.precio_base,
        cantidad: item.cantidad,
        adiciones: item.adiciones || []
      }));

      fetch(ENDPOINTS.enviarPedido, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
          mesa_id: this.mesaSeleccionada.id,
          carrito: carrito
        })
      })
      .then(async (response) => {
        const text = await response.text();
        const payload = text && text.trim().startsWith('{') ? JSON.parse(text) : {};

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${text.slice(0,200)}`);
        }

        return payload;
      })
      .then((data) => {
        if (data.success !== false) {
          // Mostrar mensaje personalizado según si fue auto-activada
          const mensaje = data.message || 'Pedido enviado correctamente. Aparecerá en comandas para ser procesado.';
          alert(mensaje);

          this.mostrarModal = false;
          this.cuentaActual = [];
          this.mesaSeleccionada = null;
          this.estadoMesa = '';

          // Debug: mostrar información adicional en consola
          console.log('Orden creada:', {
            id: data.orden_id,
            estado: data.estado_final,
            autoActivada: data.auto_activada
          });

          // Refrescar la página para mostrar el nuevo estado
          setTimeout(() => {
            window.location.reload();
          }, 1000);
        } else {
          alert(data.message || 'Error al enviar el pedido. Intenta nuevamente.');
        }
      })
      .catch((error) => {
        console.error('Error enviando pedido:', error);
        alert('Ocurrió un error al enviar el pedido. Verifica la conexión e intenta nuevamente.');
      });
    },

getEstadoEntrega(item) {
  if (this.estadoMesa === 'Libre') return 'nuevo';

  const total = Number(item?.cantidad) || 0;
  const done  = Number(item?.cantidad_entregada) || 0;

  if (total <= 0) return 'pendiente';
  if (done <= 0)  return 'pendiente';  // 👈 ya cubre "0" como string o número
  if (done >= total) return 'completo';
  return 'parcial';
},

getCantidadEntregada(item) {
  return Number.isFinite(Number(item?.cantidad_entregada))
    ? Number(item.cantidad_entregada)
    : 0;
},


getEstadoEntregaClasses(item) {
  const estado = this.getEstadoEntrega(item);
  if (this.estadoMesa === 'Libre') return 'border-gray-200';
  switch (estado) {
    case 'completo':  return 'border-green-200 bg-green-50';
    case 'parcial':   return 'border-orange-200 bg-orange-50';
    case 'pendiente': return 'border-gray-200 bg-gray-50';
    default:          return 'border-gray-200';
  }
},

getEstadoEntregaTextClass(item) {
  const estado = this.getEstadoEntrega(item);
  switch (estado) {
    case 'completo':  return 'text-green-600 font-medium';
    case 'parcial':   return 'text-orange-600 font-medium';
    case 'pendiente': return 'text-gray-500';
    default:          return 'text-gray-500';
  }
},

    getProductosCompletos() {
      return this.cuentaActual.filter(item => this.getEstadoEntrega(item) === 'completo').length;
    },

    getProductosParciales() {
      return this.cuentaActual.filter(item => this.getEstadoEntrega(item) === 'parcial').length;
    },

    getProductosPendientes() {
      return this.cuentaActual.filter(item => this.getEstadoEntrega(item) === 'pendiente').length;
    },

    getCantidadEntregada(item) {
      return item.cantidad_entregada || 0;
    },

    // Gestión de zonas
    mostrarGestionZonas: false,
    zonas: @json($zonas ?? []),
    nuevaZona: { nombre: '', descripcion: '', orden: 0 },
    zonaEditar: null,
    modoEdicion: false,
    mesasDisponibles: @json($mesasDisponibles ?? []),
    zonaAsignacion: null,
    mesasSeleccionadas: [],

    // Fusión de mesas
    modoFusion: false,
    mesasSeleccionadasFusion: [],

    async cargarZonas() {
      try {
        const response = await fetch(`{{ route('zonas.index', $restaurante ?? '') }}`, {
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (response.ok) {
          this.zonas = await response.json();
        }
      } catch (error) {
        console.error('Error al cargar zonas:', error);
      }
    },

    async crearZona() {
      if (!this.nuevaZona.nombre.trim()) {
        alert('El nombre de la zona es requerido');
        return;
      }

      try {
        const response = await fetch(`{{ route('zonas.store', $restaurante ?? '') }}`, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify(this.nuevaZona)
        });

        const data = await response.json();
        if (response.ok && data.success) {
          this.zonas.push(data.zona);
          this.nuevaZona = { nombre: '', descripcion: '', orden: 0 };
          alert(data.message || 'Zona creada exitosamente');
        } else {
          alert(data.message || 'Error al crear la zona');
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Error al crear la zona');
      }
    },

    editarZona(zona) {
      this.zonaEditar = { ...zona };
      this.modoEdicion = true;
    },

    async actualizarZona() {
      if (!this.zonaEditar.nombre.trim()) {
        alert('El nombre de la zona es requerido');
        return;
      }

      try {
        const response = await fetch(`{{ url()->current() }}/zonas/${this.zonaEditar.id}`, {
          method: 'PUT',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify(this.zonaEditar)
        });

        const data = await response.json();
        if (response.ok && data.success) {
          const index = this.zonas.findIndex(z => z.id === this.zonaEditar.id);
          if (index !== -1) {
            this.zonas[index] = data.zona;
          }
          this.cancelarEdicion();
          alert(data.message || 'Zona actualizada exitosamente');
        } else {
          alert(data.message || 'Error al actualizar la zona');
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Error al actualizar la zona');
      }
    },

    async eliminarZona(zona) {
      if (!confirm(`¿Estás seguro de eliminar la zona "${zona.nombre}"? Las mesas de esta zona quedarán sin zona asignada.`)) {
        return;
      }

      try {
        const response = await fetch(`{{ url()->current() }}/zonas/${zona.id}`, {
          method: 'DELETE',
          credentials: 'same-origin',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        const data = await response.json();
        if (response.ok && data.success) {
          this.zonas = this.zonas.filter(z => z.id !== zona.id);
          alert(data.message || 'Zona eliminada exitosamente');
        } else {
          alert(data.message || 'Error al eliminar la zona');
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Error al eliminar la zona');
      }
    },

    cancelarEdicion() {
      this.zonaEditar = null;
      this.modoEdicion = false;
    },

    abrirAsignacionMesas(zona) {
      if (this.mesasDisponibles.length === 0) {
        alert('No hay mesas disponibles para asignar. Crea mesas primero desde la gestión de mesas.');
        return;
      }

      // Cerrar el modal de gestión de zonas primero
      this.mostrarGestionZonas = false;

      // Pequeño delay para que se cierre el modal anterior
      setTimeout(() => {
        this.zonaAsignacion = zona;
        this.mesasSeleccionadas = this.mesasDisponibles
          .filter(mesa => mesa.zona_id === zona.id)
          .map(mesa => mesa.id);
      }, 100);
    },

    cerrarAsignacionMesas() {
      this.zonaAsignacion = null;
      this.mesasSeleccionadas = [];
      // Volver a mostrar el modal de gestión de zonas
      this.mostrarGestionZonas = true;
    },

    toggleMesaSeleccion(mesaId) {
      const index = this.mesasSeleccionadas.indexOf(mesaId);
      if (index > -1) {
        this.mesasSeleccionadas.splice(index, 1);
      } else {
        this.mesasSeleccionadas.push(mesaId);
      }
    },

    async asignarMesasAZona() {
      if (!this.zonaAsignacion) return;

      const url = `{{ route('zonas.asignarMesas', ['restaurante' => $restaurante, 'zona' => '__ZONA_ID__']) }}`.replace('__ZONA_ID__', this.zonaAsignacion.id);

      try {
        const response = await fetch(url, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({ mesa_ids: this.mesasSeleccionadas })
        });

        const data = await response.json();

        if (response.ok && data.success) {
          alert(data.message || 'Mesas asignadas exitosamente');
          this.cerrarAsignacionMesas();

          // Recargar el panel para mostrar los cambios
          window.location.reload();
        } else {
          alert(data.message || 'Error al asignar las mesas');
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Error al asignar las mesas');
      }
    },

    // ==================== FUSIÓN DE MESAS ====================
    toggleModoFusion() {
      this.modoFusion = !this.modoFusion;
      if (!this.modoFusion) {
        this.mesasSeleccionadasFusion = [];
      }
    },

    // Helpers para detectar si la mesa actual está fusionada
    mesaEstaFusionada() {
      if (!this.mesaSeleccionada?.id) return false;
      const mesaData = @json($mesasConEstado ?? []);
      const mesa = mesaData.find(m => m.id === this.mesaSeleccionada.id);
      return mesa?.fusionada || false;
    },

    getMesasGrupoInfo() {
      if (!this.mesaSeleccionada?.id) return '';
      const mesaData = @json($mesasConEstado ?? []);
      const mesa = mesaData.find(m => m.id === this.mesaSeleccionada.id);
      if (!mesa?.mesas_grupo) return '';
      return mesa.mesas_grupo.map(n => `M${n}`).join(', ');
    },

    toggleSeleccionMesa(mesaId, mesaNumero) {
      const index = this.mesasSeleccionadasFusion.findIndex(m => m.id === mesaId);
      if (index > -1) {
        this.mesasSeleccionadasFusion.splice(index, 1);
      } else {
        this.mesasSeleccionadasFusion.push({ id: mesaId, numero: mesaNumero });
      }
    },

    esMesaSeleccionada(mesaId) {
      return this.mesasSeleccionadasFusion.some(m => m.id === mesaId);
    },

    limpiarSeleccionFusion() {
      this.mesasSeleccionadasFusion = [];
    },

    async confirmarFusion() {
      if (this.mesasSeleccionadasFusion.length < 2) {
        alert('Debes seleccionar al menos 2 mesas para fusionar');
        return;
      }

      const mesaPrincipalId = this.mesasSeleccionadasFusion[0].id;
      const mesasSecundarias = this.mesasSeleccionadasFusion.slice(1).map(m => m.id);

      try {
        const response = await fetch(`/r/{{ $restaurante?->slug }}/mesas/fusionar`, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            mesa_principal_id: mesaPrincipalId,
            mesas_secundarias: mesasSecundarias
          })
        });

        const data = await response.json();
        if (response.ok && data.success) {
          alert(`✅ ${data.message}\nMesa principal: ${data.mesa_principal}`);
          this.modoFusion = false;
          this.mesasSeleccionadasFusion = [];
          window.location.reload();
        } else {
          alert(data.message || 'Error al fusionar las mesas');
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Error al fusionar las mesas');
      }
    },

    async confirmarTraspaso() {
      if (!this.mesaDestinoId) {
        alert('Debes seleccionar una mesa destino');
        return;
      }

      if (!this.ordenIdSeleccionada) {
        alert('No hay orden seleccionada para traspasar');
        return;
      }

      try {
        const response = await fetch(`/r/{{ $restaurante?->slug }}/ordenes/${this.ordenIdSeleccionada}/traspasar`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            mesa_destino_id: this.mesaDestinoId
          })
        });

        const data = await response.json();
        if (response.ok && data.success) {
          alert(`✅ ${data.message}`);
          this.mostrarModalTraspasar = false;
          this.mostrarModal = false;
          this.mesaDestinoId = '';
          window.location.reload();
        } else {
          alert(data.message || 'Error al traspasar la mesa');
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Error al traspasar la mesa');
      }
    },

    async desfusionarMesa(mesaId) {
      if (!confirm('¿Estás seguro de desfusionar esta mesa?')) {
        return;
      }

      try {
        const response = await fetch(`/r/{{ $restaurante?->slug }}/mesas/${mesaId}/desfusionar`, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        const data = await response.json();
        if (response.ok && data.success) {
          alert(`✅ ${data.message}`);
          window.location.reload();
        } else {
          alert(data.message || 'Error al desfusionar la mesa');
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Error al desfusionar la mesa');
      }
    },

    // ==================== GESTIÓN DE PAGOS PARCIALES ====================
    productosSeleccionados: [],

    seleccionarTodos() {
      this.productosSeleccionados = (this.ticketActual?.productos ?? [])
        .map((item, index) => {
          // No seleccionar productos ya pagados
          const cantidadPagada = item.cantidad_pagada ?? 0;
          const cantidadTotal = item.cantidad ?? 0;
          return cantidadPagada >= cantidadTotal ? null : index;
        })
        .filter(i => i !== null);
    },

    deseleccionarTodos() {
      this.productosSeleccionados = [];
    },

    estaProductoPagado(item) {
      const cantidadPagada = item.cantidad_pagada ?? 0;
      const cantidadTotal = item.cantidad ?? 0;
      return cantidadPagada >= cantidadTotal && cantidadTotal > 0;
    },

    calcularTotalSeleccionado() {
      const productos = this.ticketActual?.productos ?? [];
      return this.productosSeleccionados.reduce((total, index) => {
        const item = productos[index];
        if (!item) return total;

        const precioBase = parseFloat(item.precio_base ?? item.precio) || 0;
        const precioAdiciones = (item.adiciones ?? []).reduce((sum, a) => sum + (parseFloat(a.precio) || 0), 0);
        const cantidad = parseInt(item.cantidad) || 0;

        return total + ((precioBase + precioAdiciones) * cantidad);
      }, 0);
    },

    calcularTotalPagado() {
      const productos = this.ticketActual?.productos ?? [];
      return productos.reduce((total, item) => {
        const cantidadPagada = item.cantidad_pagada ?? 0;
        if (cantidadPagada === 0) return total;

        const precioBase = parseFloat(item.precio_base ?? item.precio) || 0;
        const precioAdiciones = (item.adiciones ?? []).reduce((sum, a) => sum + (parseFloat(a.precio) || 0), 0);

        return total + ((precioBase + precioAdiciones) * cantidadPagada);
      }, 0);
    },

    calcularTotalPendiente() {
      const totalOriginal = this.ticketActual?.total ?? 0;
      const totalPagado = this.calcularTotalPagado();
      return Math.max(0, totalOriginal - totalPagado);
    },

    async marcarSeleccionadosComoPagados() {
      if (this.productosSeleccionados.length === 0) {
        alert('No hay productos seleccionados');
        return;
      }

      if (!confirm(`¿Marcar ${this.productosSeleccionados.length} producto(s) como pagado(s)?\nTotal: €${this.calcularTotalSeleccionado().toFixed(2)}`)) {
        return;
      }

      try {
        const response = await fetch(`/r/{{ $restaurante?->slug }}/ordenes/${this.ticketActual.id}/marcar-pagados`, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            indices: this.productosSeleccionados
          })
        });

        const data = await response.json();

        if (response.ok && data.success) {
          // Actualizar ticket con datos frescos
          this.ticketActual.productos = data.orden.productos;
          this.productosSeleccionados = [];
          alert(`✅ Productos marcados como pagados\n\nTotal pagado: €${data.total_pagado.toFixed(2)}\nPendiente: €${data.total_pendiente.toFixed(2)}`);
        } else {
          alert(data.error || 'Error al marcar productos como pagados');
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Error al procesar el pago');
      }
    },

    async eliminarProductosSeleccionados() {
      if (this.productosSeleccionados.length === 0) {
        alert('No hay productos seleccionados');
        return;
      }

      if (!confirm(`⚠️ ¿Eliminar ${this.productosSeleccionados.length} producto(s) del ticket?\n\nEsta acción no se puede deshacer.`)) {
        return;
      }

      try {
        const response = await fetch(`/r/{{ $restaurante?->slug }}/ordenes/${this.ticketActual.id}/eliminar-productos`, {
          method: 'DELETE',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            indices: this.productosSeleccionados
          })
        });

        const data = await response.json();

        if (response.ok && data.success) {
          // Actualizar ticket con datos frescos
          this.ticketActual.productos = data.orden.productos;
          this.ticketActual.total = data.orden.total;
          this.productosSeleccionados = [];
          alert(`✅ ${data.mensaje}\n\nNuevo total: €${data.orden.total.toFixed(2)}`);

          // Si no quedan productos, cerrar el ticket
          if (data.orden.productos.length === 0) {
            this.mostrarTicket = false;
            window.location.reload();
          }
        } else {
          alert(data.error || 'Error al eliminar productos');
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Error al eliminar productos');
      }
    },
  };
}
</script>
