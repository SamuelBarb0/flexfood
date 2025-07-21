<x-guest-layout>
    <div class="bg-white p-8 shadow rounded-xl">
        <img src="{{ asset('images/flexfood.png') }}" alt="FlexFood Logo" class="w-36 mx-auto mb-6">

        <p class="mb-4 text-sm text-gray-700">
            ¿Olvidaste tu contraseña? No hay problema. Ingresa tu correo y te enviaremos un enlace para restablecerla.
        </p>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div>
                <x-input-label for="email" :value="'Correo electrónico'" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="flex justify-end mt-6">
                <x-primary-button class="bg-[#153958] hover:bg-[#47B19E] text-white">
                    Enviar enlace
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
