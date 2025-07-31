<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Eliminar cuenta
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Una vez que elimines tu cuenta, todos tus datos se perderán permanentemente. Asegúrate de descargar la información que desees conservar antes de continuar.
        </p>
    </header>

    <!-- Botón para abrir el modal -->
    <button
        @click="showDeleteModal = true"
        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition"
    >
        Eliminar cuenta
    </button>

    <!-- Modal -->
    <div x-data="{ showDeleteModal: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }">
        <div
            x-show="showDeleteModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        >
            <div
                @click.away="showDeleteModal = false"
                class="bg-white w-full max-w-md mx-auto rounded-lg p-6 shadow-lg"
            >
                <form method="POST" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')

                    <h2 class="text-lg font-medium text-gray-900">
                        ¿Estás seguro de que quieres eliminar tu cuenta?
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        Una vez que tu cuenta sea eliminada, toda tu información se perderá para siempre. Por favor ingresa tu contraseña para confirmar.
                    </p>

                    <!-- Campo contraseña -->
                    <div class="mt-6">
                        <label for="password" class="sr-only">Contraseña</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            placeholder="Contraseña"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-red-200"
                            required
                        >
                        @error('password', 'userDeletion')
                            <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Botones -->
                    <div class="mt-6 flex justify-end">
                        <button
                            type="button"
                            @click="showDeleteModal = false"
                            class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-200 focus:outline-none"
                        >
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="ml-3 inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition"
                        >
                            Eliminar cuenta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
