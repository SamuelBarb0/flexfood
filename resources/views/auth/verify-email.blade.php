<x-guest-layout>
    <div class="bg-white p-8 shadow rounded-xl">
        <img src="{{ asset('images/flexfood.png') }}" alt="FlexFood Logo" class="w-36 mx-auto mb-6">

        <p class="mb-4 text-sm text-gray-700">
            Gracias por registrarte. Antes de comenzar, por favor verifica tu dirección de correo electrónico usando el enlace que te enviamos.  
            Si no lo recibiste, te podemos enviar otro.
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-4 text-sm text-green-600">
                Se ha enviado un nuevo enlace a tu correo.
            </div>
        @endif

        <div class="mt-4 flex items-center justify-between">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-primary-button class="bg-[#153958] hover:bg-[#47B19E] text-white">
                    Reenviar verificación
                </x-primary-button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="underline text-sm text-[#47B19E] hover:text-[#153958]">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
