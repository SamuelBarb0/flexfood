@php
    use App\Models\Restaurante as R;

    $user = auth()->user();

    // Restaurante activo: el que venga en la vista o el del perfil del usuario
    $activeRest = $restaurante
        ?? (($user?->restaurante_id ?? null) ? R::find($user->restaurante_id) : null);

    // Regla: mostrar menú solo si el usuario tiene restaurante_id y existe el modelo
    $showMenu = ($user?->restaurante_id) && ($activeRest instanceof R) && $activeRest->exists;

    // Ajustes del sitio solo si hay menú
    $settings = $showMenu ? ($activeRest->siteSetting ?? null) : null;
@endphp

<div x-data="{ open: false }" :class="{ 'overflow-hidden': open }" class="relative">

    {{-- Barra superior / botón hamburguesa (solo móviles y solo si hay menú) --}}
    @if($showMenu)
    <div
        x-show="!open"
        x-cloak
        x-transition.opacity
        class="md:hidden flex items-center justify-between px-4 py-3 bg-white border-b shadow-sm z-[70] relative"
    >
        @if(!empty($settings?->logo_path))
            <img
                src="{{ asset($settings->logo_path) }}"
                alt="{{ $settings->site_name ?? 'Logo' }}"
                class="h-10" />
        @endif

        <button @click="open = true" class="text-gray-700 focus:outline-none" aria-label="Abrir menú">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>
    @endif

    {{-- Overlay móvil (solo si hay menú) --}}
    @if($showMenu)
    <div
        x-show="open"
        x-transition.opacity
        @click="open = false"
        class="fixed inset-0 bg-black/40 z-40 md:hidden">
    </div>
    @endif

    <!-- Aside -->
    <aside
        :class="{ 'translate-x-0': open, '-translate-x-full': !open }"
        class="fixed top-0 left-0 z-50 w-64 h-full bg-white border-r shadow-sm transform transition-transform duration-200 ease-in-out
               md:relative md:translate-x-0 md:block md:min-h-screen flex flex-col justify-between">

        {{-- Parte superior: Logo + Menú (solo si $showMenu) --}}
        @if($showMenu)
        <div>
            <div class="flex items-center justify-center h-16 px-4 py-15">
                @if(!empty($settings?->logo_path))
                    <img
                        src="{{ asset($settings->logo_path) }}"
                        alt="{{ $settings->site_name ?? 'Logo' }}"
                        class="h-24" />
                @endif
            </div>

            <nav class="px-4 py-6 space-y-2 text-sm font-medium text-gray-700">

                <!-- Dashboard -->
                <a href="{{ route('rest.dashboard', $activeRest) }}"
                   class="{{ request()->routeIs('rest.dashboard') ? 'bg-[#153958] text-white' : 'hover:bg-[#F2F2F2] text-[#153958]' }} flex items-center px-4 py-2 rounded-md transition"
                   @click="open = false">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h7v7H3V3zm0 11h7v7H3v-7zm11-11h7v7h-7V3zm0 11h7v7h-7v-7z" />
                    </svg>
                    Dashboard
                </a>

                {{-- Bloque para TODOS menos CAJERO --}}
                @if(!auth()->user()->hasRole('cajero'))

                    <!-- Comandas -->
                    <a href="{{ route('comandas.index', $activeRest) }}"
                       class="{{ request()->routeIs('comandas.*') ? 'bg-[#153958] text-white' : 'hover:bg-[#F2F2F2] text-[#153958]' }} flex items-center px-4 py-2 rounded-md transition"
                       @click="$store.ordenes.actualizarNuevas(0); open = false">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-6h13M9 5v6h13M4 6h.01M4 18h.01" />
                        </svg>
                        Comandas
                        <span
                            x-show="$store.ordenes && $store.ordenes.nuevas > 0"
                            x-text="$store.ordenes.nuevas"
                            style="display: none;"
                            class="ml-2 bg-[#3CB28B] text-white text-xs font-semibold px-2 py-0.5 rounded-full">
                        </span>
                    </a>

                    <!-- Gestor de Menú -->
                    <a href="{{ route('menu.index', $activeRest) }}"
                       class="{{ request()->routeIs('menu.*') ? 'bg-[#153958] text-white' : 'hover:bg-[#F2F2F2] text-[#153958]' }} flex items-center px-4 py-2 rounded-md transition"
                       @click="open = false">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v16a1 1 0 01-1 1H4a1 1 0 01-1-1V4z" />
                        </svg>
                        Gestor de Menú
                    </a>

                    <!-- Gestión de Mesas -->
                    <a href="{{ route('mesas.index', $activeRest) }}"
                       class="{{ request()->routeIs('mesas.*') ? 'bg-[#153958] text-white' : 'hover:bg-[#F2F2F2] text-[#153958]' }} flex items-center px-4 py-2 rounded-md transition"
                       @click="open = false">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Gestión de Mesas
                    </a>

                    <!-- Gestión de Usuarios -->
                    <a href="{{ route('users.index', $activeRest) }}"
                       class="{{ request()->routeIs('users.*') ? 'bg-[#153958] text-white' : 'hover:bg-[#F2F2F2] text-[#153958]' }} flex items-center px-4 py-2 rounded-md transition"
                       @click="open = false">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-5-4M9 20H4v-2a4 4 0 015-4m8-4a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Gestión de Usuarios
                    </a>
                @endif

                {{-- Analíticas e Historial -> admin, restauranteadmin, mesero o cajero --}}
                @if(auth()->user()->hasAnyRole(['administrador','restauranteadmin','mesero','cajero']))
                    <!-- Analíticas -->
                    <a href="{{ route('analiticas.index', $activeRest) }}"
                       class="{{ request()->routeIs('analiticas.*') ? 'bg-[#153958] text-white' : 'hover:bg-[#F2F2F2] text-[#153958]' }} flex items-center px-4 py-2 rounded-md transition"
                       @click="open = false">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M9 17V9M13 17v-4M17 17V5" />
                        </svg>
                        Analíticas
                    </a>

                    <!-- Historial de Mesas -->
                    <a href="{{ route('historial.mesas', $activeRest) }}"
                       class="{{ request()->routeIs('historial.*') ? 'bg-[#153958] text-white' : 'hover:bg-[#F2F2F2] text-[#153958]' }} flex items-center px-4 py-2 rounded-md transition"
                       @click="open = false">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3M12 6a9 9 0 110 18 9 9 0 010-18z" />
                        </svg>
                        Historial de Mesas
                    </a>
                @endif

                {{-- Configuración -> admin o restauranteadmin --}}
                @if(auth()->user()->hasAnyRole(['administrador','restauranteadmin']))
                    <a href="{{ route('settings.edit', $activeRest) }}"
                       class="{{ request()->routeIs('settings.*') ? 'bg-[#153958] text-white' : 'hover:bg-[#F2F2F2] text-[#153958]' }} flex items-center px-4 py-2 rounded-md transition"
                       @click="open = false">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.57-.907 3.356.879 2.45 2.45a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.907 1.57-.879 3.356-2.45 2.45a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.57.907-3.356-.879-2.45-2.45a1.724 1.724 0 00-1.066-2.573c-1.756.426-1.756 2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.907-1.57.879-3.356 2.45-2.45.97.56 2.2.164 2.573-1.066z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Configuración
                    </a>
                @endif

                {{-- Restaurantes (solo admin global) --}}
                @if(auth()->user()->hasRole('administrador'))
                    <a href="{{ route('restaurantes.index') }}"
                       class="{{ request()->routeIs('restaurantes.*') ? 'bg-[#153958] text-white' : 'hover:bg-[#F2F2F2] text-[#153958]' }} flex items-center px-4 py-2 rounded-md transition"
                       @click="open = false">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3 10l9-7 9 7v8a2 2 0 01-2 2h-4a2 2 0 01-2-2V13H9v5a2 2 0 01-2 2H3a2 2 0 01-2-2v-8z" />
                        </svg>
                        Restaurantes
                    </a>
                @endif
            </nav>
        </div>
        @endif {{-- /$showMenu --}}

        <!-- Parte inferior: Perfil (siempre visible) -->
        <div class="border-t px-4 py-4 bg-[#f9f9f9]">
            <div class="flex items-center space-x-3">
                <div class="bg-[#153958] text-white rounded-full h-8 w-8 flex items-center justify-center font-bold">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <div class="flex flex-col text-sm">
                    <p class="text-[#153958] font-semibold">{{ auth()->user()->name ?? 'Usuario' }}</p>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-xs text-red-500 hover:underline text-left">Cerrar sesión</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Scripts de notificaciones en tiempo real SOLO si hay menú --}}
        @if($showMenu)
        <script>
            console.log('🎬 Navigation script cargado - showMenu = true');

            // IMPORTANTE: Este script se ejecuta ANTES de que Alpine inicie
            // para que el store esté disponible cuando Alpine procese el DOM

            // Sistema de audio para notificaciones
            let audioContextoNav = null;

            // Crear AudioContext inmediatamente (puede estar suspended)
            try {
              audioContextoNav = new (window.AudioContext || window.webkitAudioContext)();
              console.log('✅ Navigation - AudioContext creado, estado:', audioContextoNav.state);

              // Si está suspended, mostrar mensaje al usuario
              if (audioContextoNav.state === 'suspended') {
                console.log('⚠️ Navigation - Audio suspendido - requiere interacción del usuario');
              }
            } catch (err) {
              console.error('❌ Navigation - Error creando AudioContext:', err);
            }

            // Función para resume audio
            function activarAudioNav() {
              if (audioContextoNav && audioContextoNav.state === 'suspended') {
                return audioContextoNav.resume().then(() => {
                  console.log('✅ Navigation - AudioContext activado automáticamente');
                  return true;
                }).catch(err => {
                  console.warn('⚠️ Navigation - No se pudo activar audio automáticamente:', err);
                  return false;
                });
              }
              return Promise.resolve(true);
            }

            // Auto-click invisible para activar audio sin intervención del usuario
            document.addEventListener('DOMContentLoaded', () => {
              const btnInvisible = document.createElement('button');
              btnInvisible.style.position = 'fixed';
              btnInvisible.style.top = '-9999px';
              btnInvisible.style.left = '-9999px';
              btnInvisible.style.width = '1px';
              btnInvisible.style.height = '1px';
              btnInvisible.style.opacity = '0';
              btnInvisible.setAttribute('aria-hidden', 'true');

              btnInvisible.addEventListener('click', () => {
                console.log('🎯 Navigation - Click invisible ejecutado - activando audio...');
                activarAudioNav().then(() => {
                  btnInvisible.remove();
                });
              });

              document.body.appendChild(btnInvisible);

              // Ejecutar click automático después de 100ms
              setTimeout(() => {
                console.log('🚀 Ejecutando click automático...');
                btnInvisible.click();
              }, 100);
            }, { once: true });

            // Función para reproducir sonido de notificación
            function playNotificationSound() {
                const soundPath = "{{ !empty($activeRest->notification_sound_path) ? asset($activeRest->notification_sound_path) : '' }}";

                if (soundPath) {
                    // Reproducir sonido personalizado
                    console.log('🔊 Reproduciendo sonido personalizado:', soundPath);
                    const audio = new Audio(soundPath);
                    audio.play().catch(err => {
                        console.error('❌ Error al reproducir sonido:', err);
                        // Si falla, usar beep predeterminado
                        playDefaultBeep();
                    });
                } else {
                    // Beep predeterminado
                    console.log('🔊 Reproduciendo beep predeterminado');
                    playDefaultBeep();
                }
            }

            function playDefaultBeep() {
              if (!audioContextoNav) {
                console.warn('⚠️ Navigation - Audio context no inicializado aún');
                return;
              }

              try {
                const oscillator = audioContextoNav.createOscillator();
                const gainNode = audioContextoNav.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(audioContextoNav.destination);

                oscillator.frequency.value = 800; // Frecuencia del beep
                oscillator.type = 'sine';

                gainNode.gain.setValueAtTime(0.3, audioContextoNav.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContextoNav.currentTime + 0.5);

                oscillator.start(audioContextoNav.currentTime);
                oscillator.stop(audioContextoNav.currentTime + 0.5);
              } catch (err) {
                console.error('❌ Navigation - Error al crear beep:', err);
              }
            }

            // NOTA: El store 'ordenes' se crea en app.js ANTES de Alpine.start()
            // para que esté disponible cuando Alpine procese el DOM

            // Función de inicialización de listeners
            function inicializarNotificaciones() {
                console.log('🚀 Inicializando listeners de notificaciones...');
                console.log('🔍 Alpine disponible:', typeof Alpine !== 'undefined');
                console.log('🔍 Echo disponible:', typeof window.Echo !== 'undefined');

                // Verificar reactividad del store
                Alpine.effect(() => {
                    console.log('👁️ Alpine detectó cambio en store.ordenes.nuevas:', Alpine.store('ordenes').nuevas);
                });

                const urlNuevas = "{{ route('comandas.nuevas', $activeRest) }}";
                const restauranteSlug = "{{ $activeRest->slug }}";

                console.log('📍 URL:', urlNuevas);
                console.log('🏪 Slug:', restauranteSlug);

                // Función para actualizar el contador
                const actualizarContador = () => {
                    console.log('🔄 Actualizando contador...');
                    fetch(urlNuevas, {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' }
                    })
                    .then(res => res.ok ? res.json() : Promise.reject(res.statusText))
                    .then(data => {
                        console.log('📊 Respuesta:', data);
                        if (data.nuevas !== undefined) {
                            Alpine.store('ordenes').actualizarNuevas(data.nuevas);
                            console.log('✅ Badge actualizado:', data.nuevas);
                        }
                    })
                    .catch(err => console.error('❌ Error:', err));
                };

                // Configurar Pusher para notificaciones en tiempo real
                if (window.Echo) {
                    console.log('🎯 Echo detectado, configurando listener...');
                    console.log('📡 Estado de Echo:', window.Echo);

                    const channel = window.Echo.channel(`restaurante.${restauranteSlug}`);
                    console.log('📺 Canal creado:', channel);

                    channel.listen('.orden.cambio', (e) => {
                        console.log('🔔 Evento Pusher recibido en navigation:', e);
                        console.log('📦 Store actual antes de actualizar:', Alpine.store('ordenes').nuevas);

                        // Si es una nueva orden pendiente, incrementar el contador inmediatamente
                        if (e.action === 'crear' && e.estado === 0) {
                            console.log('🆕 Nueva orden detectada');

                            // Incrementar badge inmediatamente (optimistic update)
                            const nuevoValor = Alpine.store('ordenes').nuevas + 1;
                            Alpine.store('ordenes').actualizarNuevas(nuevoValor);
                            console.log('⚡ Badge actualizado optimísticamente:', nuevoValor);

                            // Reproducir sonido DESPUÉS de actualizar el badge
                            playNotificationSound();

                            // Verificar con el servidor después (para corregir si hay diferencias)
                            setTimeout(() => {
                                console.log('🔄 Verificando con servidor...');
                                actualizarContador();
                            }, 1000);
                        } else {
                            // Para otros eventos (activar, entregar, etc.), actualizar desde servidor
                            console.log('🔄 Actualizando desde servidor...');
                            setTimeout(() => {
                                actualizarContador();
                            }, 500);
                        }
                    });

                    // Escuchar eventos de conexión
                    channel.on('pusher:subscription_succeeded', () => {
                        console.log('✅ Suscripción exitosa al canal:', `restaurante.${restauranteSlug}`);
                    });

                    channel.on('pusher:subscription_error', (error) => {
                        console.error('❌ Error de suscripción:', error);
                    });

                    console.log('✅ Pusher configurado en:', `restaurante.${restauranteSlug}`);

                    // Actualizar inmediatamente al cargar (con delay mayor)
                    console.log('⏱️ Actualizando contador inicial en 300ms...');
                    setTimeout(actualizarContador, 300);
                } else {
                    console.warn('⚠️ Echo no disponible, usando polling');
                    // Actualizar inmediatamente
                    actualizarContador();
                    // Polling cada 5 segundos
                    setInterval(actualizarContador, 5000);
                }
            }

            // Ejecutar cuando el DOM esté listo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    console.log('📄 Navigation - DOM cargado');
                    setTimeout(inicializarNotificaciones, 500);
                });
            } else {
                console.log('📄 Navigation - DOM ya listo');
                setTimeout(inicializarNotificaciones, 500);
            }

            // ==========================================
            // PROCESADOR DE COLA AUTOMÁTICO (Tiempo Real)
            // ==========================================
            // Llamar al endpoint cada 5 segundos para procesar cola
            function procesarColaAutomaticamente() {
                fetch('{{ route("queue.process") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    console.log('🔄 Cola:', data.status, data.message || '');
                })
                .catch(err => {
                    console.warn('⚠️ Error procesando cola:', err);
                });
            }

            // Ejecutar inmediatamente y luego cada 5 segundos
            procesarColaAutomaticamente();
            setInterval(procesarColaAutomaticamente, 5000);
        </script>
        @endif

    </aside>
</div>
