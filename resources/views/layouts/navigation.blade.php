<aside class="w-64 min-h-screen bg-white border-r shadow-sm flex flex-col justify-between">
    <!-- Parte superior: Logo + Menú -->
    <div>
        <div class="flex items-center justify-center h-16 border-b px-4 py-4">
            <img src="{{ asset('images/flexfood.png') }}" alt="Logo FlexFood" class="h-24">
        </div>

        <!-- Menú lateral -->
        <nav x-data="{ ordenesNuevas: parseInt(localStorage.getItem('ordenesNuevas') || 0) }" class="px-4 py-6 space-y-2 text-sm font-medium text-gray-700">

            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}"
                class="{{ request()->routeIs('dashboard') ? 'bg-[#153958] text-white' : 'hover:bg-[#F2F2F2] text-[#153958]' }} flex items-center px-4 py-2 rounded-md transition">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 3h7v7H3V3zm0 11h7v7H3v-7zm11-11h7v7h-7V3zm0 11h7v7h-7v-7z" />
                </svg>
                Dashboard
            </a>

            <!-- Comandas con notificación -->
            <a href="{{ route('comandas.index') }}"
                class="flex items-center px-4 py-2 rounded-md text-[#153958] hover:bg-[#F2F2F2]"
                @click="ordenesNuevas = 0; localStorage.setItem('ordenesNuevas', '0')">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-6h13M9 5v6h13M4 6h.01M4 18h.01" />
                </svg>
                Comandas

                <template x-if="ordenesNuevas > 0">
                    <span class="ml-2 bg-[#3CB28B] text-white text-xs font-semibold px-2 py-0.5 rounded-full"
                        x-text="ordenesNuevas"></span>
                </template>
            </a>

            <!-- Gestor de Menú -->
            <a href="{{ route('menu.index') }}" class="flex items-center px-4 py-2 rounded-md text-[#153958] hover:bg-[#F2F2F2]">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v16a1 1 0 01-1 1H4a1 1 0 01-1-1V4z" />
                </svg>
                Gestor de Menú
            </a>

            <!-- Gestión de Mesas -->
            <a href="{{ route('mesas.index') }}" class="flex items-center px-4 py-2 rounded-md text-[#153958] hover:bg-[#F2F2F2]">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Gestión de Mesas
            </a>

            <!-- Analíticas -->
            <a href="#" class="flex items-center px-4 py-2 rounded-md text-[#153958] hover:bg-[#F2F2F2]">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 3v18h18M9 17V9M13 17v-4M17 17V5" />
                </svg>
                Analíticas
            </a>

            <!-- Historial de Mesas -->
            <a href="#" class="flex items-center px-4 py-2 rounded-md text-[#153958] hover:bg-[#F2F2F2]">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 8v4l3 3M12 6a9 9 0 110 18 9 9 0 010-18z" />
                </svg>
                Historial de Mesas
            </a>

            <!-- Registro de Cambios -->
            <a href="#" class="flex items-center px-4 py-2 rounded-md text-[#153958] hover:bg-[#F2F2F2]">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 6h16M4 12h8m-8 6h16" />
                </svg>
                Registro de Cambios
            </a>
        </nav>
    </div>

    <!-- Parte inferior: Perfil -->
    <div class="border-t px-4 py-4 bg-[#f9f9f9]">
        <div class="flex items-center space-x-3">
            <div class="bg-[#153958] text-white rounded-full h-8 w-8 flex items-center justify-center font-bold">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>
            <div class="flex flex-col text-sm">
                <p class="text-[#153958] font-semibold">{{ auth()->user()->name ?? 'Usuario' }}</p>
                <a href="{{ route('profile.edit') }}" class="text-xs text-gray-600 hover:underline">Mi perfil</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs text-red-500 hover:underline text-left">Cerrar sesión</button>
                </form>
            </div>
        </div>
    </div>
</aside>