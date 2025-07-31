<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Actualizar contraseña
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Asegúrate de usar una contraseña larga y segura.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <!-- Contraseña actual -->
        <div>
            <label for="update_password_current_password" class="block text-sm font-medium text-gray-700">Contraseña actual</label>
            <input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-indigo-200" autocomplete="current-password">
            @error('current_password', 'updatePassword')
                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
            @enderror
        </div>

        <!-- Nueva contraseña -->
        <div>
            <label for="update_password_password" class="block text-sm font-medium text-gray-700">Nueva contraseña</label>
            <input id="update_password_password" name="password" type="password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-indigo-200" autocomplete="new-password">
            @error('password', 'updatePassword')
                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
            @enderror
        </div>

        <!-- Confirmación de contraseña -->
        <div>
            <label for="update_password_password_confirmation" class="block text-sm font-medium text-gray-700">Confirmar nueva contraseña</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-indigo-200" autocomplete="new-password">
            @error('password_confirmation', 'updatePassword')
                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
            @enderror
        </div>

        <!-- Botón de guardar -->
        <div class="flex items-center gap-4">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-[#153958] border border-transparent rounded-md font-semibold text-sm text-white hover:bg-[#1e4d7a] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                Guardar cambios
            </button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition
                   x-init="setTimeout(() => show = false, 2000)"
                   class="text-sm text-gray-600">
                    Guardado.
                </p>
            @endif
        </div>
    </form>
</section>
