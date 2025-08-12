{{-- Modal: Editar --}}
<div
    x-show="showEdit"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center"
    @keydown.escape.window="closeEdit()">
    <!-- Overlay -->
    <div class="absolute inset-0 bg-black/50"
        x-transition.opacity></div>

    <!-- Dialog -->
    <div class="relative bg-white w-full max-w-lg rounded-2xl shadow p-6"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95">

        <button class="absolute top-3 right-3 text-gray-500 hover:text-gray-700" @click="closeEdit()">✕</button>
        <h2 class="text-xl font-semibold text-[#153958] mb-4">Editar Restaurante</h2>

        <form method="POST" :action="editForm.updateUrl" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium">Nombre</label>
                <input type="text" name="nombre" x-model="editForm.nombre" class="mt-1 w-full border rounded px-3 py-2" required>
            </div>

            <div>
                <label class="block text-sm font-medium">Slug</label>
                <input type="text" name="slug" x-model="editForm.slug" class="mt-1 w-full border rounded px-3 py-2">
                <p class="text-xs text-gray-500 mt-1">Si lo cambias, se actualizarán las URLs con /r/{slug}.</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Usuarios asignados</label>
                <select id="edit-usuarios" name="usuarios[]" multiple class="w-full border rounded px-3 py-2 h-40">
                    @foreach($users as $u)
                    @php
                    $rid = (int) ($u->restaurante_id ?? 0);
                    @endphp
                    <option
                        value="{{ $u->id }}"
                        {{-- Mostrar SOLO si el usuario no tiene restaurante o si pertenece al restaurante que se está editando --}}
                        x-show="{{ $rid === 0 ? 'true' : 'editForm.id === ' . $rid }}"
                        {{-- Si pertenece a otro restaurante, inhabilitarlo por si el navegador lo mantiene seleccionado --}}
                        x-bind:disabled="{{ $rid === 0 ? 'false' : 'editForm.id !== ' . $rid }}">
                        {{ $u->name }} ({{ $u->email }}){{ $rid ? ' — asignado' : '' }}
                    </option>
                    @endforeach
                </select>

                <p class="text-xs text-gray-500 mt-1">Los usuarios no seleccionados serán desasignados.</p>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="closeEdit()" class="px-4 py-2 bg-gray-100 rounded">Cancelar</button>
                <button class="px-4 py-2 bg-[#3CB28B] text-white rounded">Guardar</button>
            </div>
        </form>
    </div>
</div>