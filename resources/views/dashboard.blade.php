<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard de Estado') }}
        </h2>
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 bg-gray-100 min-h-screen">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800">Estado de las Mesas</h3>
        </div>

        <div class="grid grid-cols-4 gap-4 mb-4">
            <!-- Mesa 1 -->
            <div class="bg-gray-300 text-center rounded-md p-4">
                <p class="text-xl font-bold">1</p>
                <p>Libre</p>
                <p class="text-sm">- €</p>
            </div>

            <!-- Mesa 2 -->
            <div class="bg-blue-500 text-white text-center rounded-md p-4">
                <p class="text-xl font-bold">2</p>
                <p>Ocupada</p>
                <p class="text-sm">05:01</p>
                <p class="text-sm">- €</p>
            </div>

            <!-- Mesa 3 -->
            <div class="bg-gray-300 text-center rounded-md p-4">
                <p class="text-xl font-bold">3</p>
                <p>Libre</p>
                <p class="text-sm">- €</p>
            </div>

            <!-- Mesa 4 -->
            <div class="bg-gray-300 text-center rounded-md p-4">
                <p class="text-xl font-bold">4</p>
                <p>Libre</p>
                <p class="text-sm">- €</p>
            </div>

            <!-- Mesa 5 -->
            <div class="bg-gray-300 text-center rounded-md p-4">
                <p class="text-xl font-bold">5</p>
                <p>Libre</p>
                <p class="text-sm">- €</p>
            </div>

            <!-- Mesa 6 -->
            <div class="bg-gray-300 text-center rounded-md p-4">
                <p class="text-xl font-bold">6</p>
                <p>Libre</p>
                <p class="text-sm">- €</p>
            </div>

            <!-- Mesa 7 -->
            <div class="bg-green-500 text-white text-center rounded-md p-4">
                <p class="text-xl font-bold">7</p>
                <p>Activa</p>
                <p class="text-sm">25:01</p>
                <p class="text-sm font-bold">5.00 €</p>
            </div>

            <!-- Mesa 8 -->
            <div class="bg-gray-300 text-center rounded-md p-4">
                <p class="text-xl font-bold">8</p>
                <p>Libre</p>
                <p class="text-sm">- €</p>
            </div>

            <!-- Mesa 9 -->
            <div class="bg-green-500 text-white text-center rounded-md p-4">
                <p class="text-xl font-bold">9</p>
                <p>Activa</p>
                <p class="text-sm">1h 02m</p>
                <p class="text-sm font-bold">22.50 €</p>
            </div>

            <!-- Mesa 10 -->
            <div class="bg-gray-300 text-center rounded-md p-4">
                <p class="text-xl font-bold">10</p>
                <p>Libre</p>
                <p class="text-sm">- €</p>
            </div>

            <!-- Mesa 11 -->
            <div class="bg-orange-500 text-white text-center rounded-md p-4">
                <p class="text-xl font-bold">11</p>
                <p>Pide la Cuenta</p>
                <p class="text-sm">40:01</p>
                <p class="text-sm font-bold">22.00 €</p>
            </div>

            <!-- Mesa 12 -->
            <div class="bg-gray-300 text-center rounded-md p-4">
                <p class="text-xl font-bold">12</p>
                <p>Libre</p>
                <p class="text-sm">- €</p>
            </div>
        </div>

        <div class="mt-6 flex justify-between items-center">
            <div class="bg-green-100 text-green-800 text-sm font-bold px-4 py-2 rounded">
                Ingresos Activos Totales: 49.50 €
            </div>

            <div class="bg-white shadow rounded-md p-4 w-1/3">
                <h4 class="text-md font-semibold mb-2 text-gray-800 flex items-center">
                    <svg class="h-4 w-4 mr-2 text-gray-600" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8 7V3m8 4V3m-9 4h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Actividad Reciente
                </h4>
                <p class="text-sm text-gray-500">Sin actividad reciente</p>
            </div>
        </div>
    </div>
</x-app-layout>
