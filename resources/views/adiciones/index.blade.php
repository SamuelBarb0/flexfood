@extends('layouts.app')

@section('title', 'Gestor de Adiciones')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="adicionesHandler()">

    <!-- Encabezado -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-[#153958]">Gestor de Adiciones</h1>
        <button @click="abrirCrearModal"
            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md shadow">
            + Nueva Adición
        </button>
    </div>

    <!-- Lista de adiciones -->
    <div class="space-y-4">
        <template x-for="(adicion, index) in adiciones" :key="adicion.id">
            <div class="bg-white shadow rounded-md p-4 flex justify-between items-center">
                <div>
                    <p class="text-lg font-semibold text-[#153958]" x-text="adicion.nombre"></p>
                    <template x-if="adicion.precio">
                        <p class="text-sm text-gray-600">
                            Precio: €<span x-text="parseFloat(adicion.precio).toFixed(2)"></span>
                        </p>
                    </template>
                </div>
                <div class="flex gap-3 text-sm">
                    <button @click="abrirEditarModal(adicion)" class="text-blue-600 hover:underline">Editar</button>
                    <button @click="eliminarAdicion(adicion.id)" class="text-red-600 hover:underline">Eliminar</button>
                </div>
            </div>
        </template>
    </div>

    <!-- Modales -->
    @include('adiciones.partials.modal-crear')
    @include('adiciones.partials.modal-editar')

</div>
@endsection


<script>
    function adicionesHandler() {
        return {
            showModalCreate: false,
            showModalEdit: false,
            form: { id: null, nombre: '', precio: null },
            adiciones: @json($adiciones),

            abrirCrearModal() {
                this.form = { id: null, nombre: '', precio: null };
                this.showModalCreate = true;
            },

            crearAdicion() {
                fetch(`{{ route('adiciones.store') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.form)
                })
                .then(res => res.json())
                .then(data => {
                    this.adiciones.push(data);
                    this.adiciones = [...this.adiciones]; // Forzar reactividad
                    this.showModalCreate = false;
                })
                .catch(err => console.error('Error al crear:', err));
            },

            abrirEditarModal(adicion) {
                this.form = { ...adicion };
                this.showModalEdit = true;
            },

            actualizarAdicion() {
                const url = `{{ route('adiciones.update', ':id') }}`.replace(':id', this.form.id);
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-HTTP-Method-Override': 'PUT'
                    },
                    body: JSON.stringify(this.form)
                })
                .then(res => res.json())
                .then(data => {
                    const index = this.adiciones.findIndex(a => a.id === data.id);
                    if (index !== -1) this.adiciones[index] = data;
                    this.adiciones = [...this.adiciones];
                    this.showModalEdit = false;
                })
                .catch(err => console.error('Error al actualizar:', err));
            },

            eliminarAdicion(id) {
                if (!confirm('¿Eliminar esta adición?')) return;

                const url = `{{ route('adiciones.destroy', ':id') }}`.replace(':id', id);
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-HTTP-Method-Override': 'DELETE'
                    }
                })
                .then(res => res.json())
                .then(() => {
                    this.adiciones = this.adiciones.filter(a => a.id !== id);
                })
                .catch(err => console.error('Error al eliminar:', err));
            }
        }
    }
</script>

