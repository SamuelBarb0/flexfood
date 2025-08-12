@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto p-6" x-data="restaurantesUI()">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
        <h1 class="text-2xl font-bold text-[#153958]">Restaurantes</h1>
        <div class="flex items-center gap-2">
            <form method="GET" action="{{ route('restaurantes.index') }}" class="flex items-center gap-2">
                <input type="text" name="q" value="{{ $q }}"
                       placeholder="Buscar por nombre o slug..."
                       class="border rounded px-3 py-2 w-64">
                <button class="px-3 py-2 bg-gray-100 rounded">Buscar</button>
            </form>
            <button @click="openCreate()" class="px-4 py-2 bg-[#3CB28B] text-white rounded-md">Nuevo</button>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 text-green-700 rounded">{{ session('success') }}</div>
    @endif

    <div class="bg-white shadow rounded-lg divide-y">
        @forelse($restaurantes as $r)
            <div class="p-4 flex items-center justify-between">
                <div>
                    <div class="font-semibold">{{ $r->nombre }}</div>
                    <div class="text-sm text-gray-500">/{{ $r->slug }} · Usuarios: {{ $r->users_count }}</div>
                </div>
                <div class="flex gap-2">
@php
    $payload = [
        'id'        => $r->id,
        'nombre'    => $r->nombre,
        'slug'      => $r->slug,
        'updateUrl' => route('restaurantes.update', $r),
        'usuarios'  => $r->users->pluck('id')->toArray(), // usa la relación cargada
    ];
@endphp

<button
    type="button"
    data-payload='@json($payload)'
    @click='openEdit(JSON.parse($el.dataset.payload))'
    class="px-3 py-1 bg-gray-100 rounded"
>
    Editar
</button>



                    <form method="POST" action="{{ route('restaurantes.destroy', $r) }}" onsubmit="return confirm('¿Eliminar restaurante?');">
                        @csrf @method('DELETE')
                        <button class="px-3 py-1 bg-red-100 text-red-700 rounded">Eliminar</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="p-6 text-gray-500">No hay restaurantes.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $restaurantes->links() }}</div>

    {{-- Modales (partials) --}}
    @include('restaurantes.partials.modal-create', ['users' => $usersUnassigned])
@include('restaurantes.partials.modal-edit',   ['users' => $usersAll])
</div>

{{-- Alpine helpers para los modales --}}
<script>
function restaurantesUI() {
    return {
        showCreate: false,
        showEdit: false,
        editForm: {
            id: null,          // 👈 AÑADIDO
            updateUrl: '',
            nombre: '',
            slug: '',
            usuarios: [],
        },
        openCreate() { this.showCreate = true; },
        closeCreate() { this.showCreate = false; },

        openEdit(payload) {
            this.editForm.id        = payload.id;        // 👈 AÑADIDO
            this.editForm.updateUrl = payload.updateUrl;
            this.editForm.nombre    = payload.nombre ?? '';
            this.editForm.slug      = payload.slug ?? '';
            this.editForm.usuarios  = Array.isArray(payload.usuarios) ? payload.usuarios : [];
            this.showEdit = true;

            this.$nextTick(() => {
                const sel = document.getElementById('edit-usuarios');
                if (sel) {
                    Array.from(sel.options).forEach(opt => {
                        opt.selected = this.editForm.usuarios.includes(parseInt(opt.value));
                    });
                }
            });
        },
        closeEdit() { this.showEdit = false; },
    };
}
</script>
@endsection
