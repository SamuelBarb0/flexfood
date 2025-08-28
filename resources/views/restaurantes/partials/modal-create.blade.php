{{-- Modal: Crear --}}
<div
    x-show="showCreate"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center"
    @keydown.escape.window="closeCreate()">
    <div class="absolute inset-0 bg-black/50" x-transition.opacity></div>

    <div class="relative bg-white w-full max-w-lg rounded-2xl shadow p-6"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <button class="absolute top-3 right-3 text-gray-500 hover:text-gray-700" @click="closeCreate()">✕</button>
        <h2 class="text-xl font-semibold text-[#153958] mb-4">Nuevo Restaurante</h2>

        <form method="POST" action="{{ route('restaurantes.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium">Nombre</label>
                <input type="text" name="nombre" class="mt-1 w-full border rounded px-3 py-2" required>
            </div>

            <div>
                <label class="block text-sm font-medium">Slug (opcional)</label>
                <input type="text" name="slug" placeholder="Si lo dejas vacío, se genera automáticamente"
                       class="mt-1 w-full border rounded px-3 py-2">
            </div>

            {{-- 👇 Campo de Plan --}}
            <div>
                <label class="block text-sm font-medium">Plan</label>
                <select name="plan" class="mt-1 w-full border rounded px-3 py-2">
                    <option value="">Legacy (sin plan)</option>
                    <option value="basic">Basic</option>
                    <option value="advanced">Advanced</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">
                    Basic: 50 platos, 15 QR, 3 perfiles. Advanced: platos ilimitados, 30 QR, 7 perfiles.
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Asignar usuarios (opcional)</label>
                <select name="usuarios[]" multiple class="w-full border rounded px-3 py-2 h-40">
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">
                            {{ $u->name }} ({{ $u->email }}) {{ $u->restaurante_id ? '— ya asignado' : '' }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Mantén presionado Ctrl/⌘ para seleccionar varios.</p>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="closeCreate()" class="px-4 py-2 bg-gray-100 rounded">Cancelar</button>
                <button class="px-4 py-2 bg-[#3CB28B] text-white rounded">Crear</button>
            </div>
        </form>
    </div>
</div>
