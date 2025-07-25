<div x-show="showModalCreate"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95"
     class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center"
     @click.away="showModalCreate = false">

    <div class="bg-white p-6 rounded shadow-md w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Crear Adición</h2>
        <form @submit.prevent="crearAdicion">
            <div class="mb-4">
                <label class="block text-sm font-medium">Nombre</label>
                <input type="text" x-model="form.nombre" class="w-full border rounded px-3 py-2">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium">Precio (€)</label>
                <input type="number" x-model="form.precio" class="w-full border rounded px-3 py-2" step="0.01">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" @click="showModalCreate = false" class="px-4 py-2 bg-gray-300 rounded">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Guardar</button>
            </div>
        </form>
    </div>
</div>
