<x-guest-layout>
    <div class="bg-white p-8 shadow rounded-xl">
        <img src="{{ asset('images/flexfood.png') }}" alt="FlexFood Logo" class="w-36 mx-auto mb-6">

        <h2 class="text-2xl font-bold text-center text-[#153958] mb-6">Crear cuenta en FlexFood</h2>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div>
                <x-input-label for="name" :value="'Nombre completo'" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="email" :value="'Correo electrónico'" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="password" :value="'Contraseña'" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="password_confirmation" :value="'Confirmar contraseña'" />
                <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="flex items-center justify-between mt-6">
                <a class="underline text-sm text-[#47B19E] hover:text-[#153958]" href="{{ route('login') }}">
                    ¿Ya tienes una cuenta?
                </a>

                <x-primary-button class="bg-[#153958] hover:bg-[#47B19E] text-white px-4 py-2 rounded-md">
                    Registrarse
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
