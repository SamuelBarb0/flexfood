<x-guest-layout>
    <div class="bg-white p-8 shadow rounded-xl">
        <img src="{{ asset('images/flexfood.png') }}" alt="FlexFood Logo" class="w-36 mx-auto mb-6">

        <p class="mb-4 text-sm text-gray-700">
            Esta es una zona segura. Por favor confirma tu contraseña para continuar.
        </p>

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div>
                <x-input-label for="password" :value="'Contraseña'" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="flex justify-end mt-6">
                <x-primary-button class="bg-[#153958] hover:bg-[#47B19E] text-white">
                    Confirmar
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
