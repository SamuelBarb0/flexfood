@extends('layouts.app')

@section('title', 'Gestor de Adiciones')

@section('content')
@php($restaurante = $restaurante ?? request()->route('restaurante'))

<div class="container mx-auto px-4 py-6" x-data="adicionesHandler()" x-init="init">

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

                    <template x-if="adicion.precio !== null && adicion.precio !== undefined">
                        <p class="text-sm text-gray-600">
                            Precio: €<span x-text="parseFloat(adicion.precio || 0).toFixed(2)"></span>
                        </p>
                    </template>

                    <template x-if="adicion.categorias && adicion.categorias.length">
                        <p class="text-sm text-gray-500 mt-1">
                            Categorías:
                            <template x-for="(cat, i) in adicion.categorias" :key="cat.id">
                                <span x-text="cat.nombre + (i < adicion.categorias.length - 1 ? ', ' : '')"></span>
                            </template>
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
    @include('adiciones.partials.modal-crear', ['restaurante' => $restaurante])
    @include('adiciones.partials.modal-editar', ['restaurante' => $restaurante])

</div>
@endsection


<script>
    function adicionesHandler() {
        // URLs seguras para Alpine
        const API_CATS     = @js(route('api.categorias', $restaurante));
        const STORE_URL    = @js(route('adiciones.store', $restaurante));
        const UPDATE_BASE  = @js(route('adiciones.update', [$restaurante, '__ID__']));
        const DESTROY_BASE = @js(route('adiciones.destroy', [$restaurante, '__ID__']));

        return {
            showModalCreate: false,
            showModalEdit: false,
            categorias: [],

            form: {
                id: null,
                nombre: '',
                precio: null,
                categoria_id: []
            },

            adiciones: @json($adiciones),

            async init() {
                try {
                    const res = await fetch(API_CATS);
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    this.categorias = await res.json();
                } catch (error) {
                    console.error('Error al cargar categorías:', error);
                }
            },

            abrirCrearModal() {
                this.form = { id: null, nombre: '', precio: null, categoria_id: [] };
                this.showModalCreate = true;
            },

            async crearAdicion() {
                try {
                    const res = await fetch(STORE_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(this.form)
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();
                    this.adiciones.push(data);
                    this.adiciones = [...this.adiciones];
                    this.showModalCreate = false;
                } catch (err) {
                    console.error('Error al crear:', err);
                }
            },

            abrirEditarModal(adicion) {
                this.form = {
                    id: adicion.id,
                    nombre: adicion.nombre,
                    precio: adicion.precio,
                    categoria_id: (adicion.categorias?.map(c => c.id)) ?? []
                };
                this.showModalEdit = true;
            },

            async actualizarAdicion() {
                try {
                    const url = UPDATE_BASE.replace('__ID__', this.form.id);
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-HTTP-Method-Override': 'PUT'
                        },
                        body: JSON.stringify(this.form)
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();
                    const idx = this.adiciones.findIndex(a => a.id === data.id);
                    if (idx !== -1) this.adiciones[idx] = data;
                    this.adiciones = [...this.adiciones];
                    this.showModalEdit = false;
                } catch (err) {
                    console.error('Error al actualizar:', err);
                }
            },

            async eliminarAdicion(id) {
                if (!confirm('¿Eliminar esta adición?')) return;

                try {
                    const url = DESTROY_BASE.replace('__ID__', id);
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-HTTP-Method-Override': 'DELETE'
                        }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    this.adiciones = this.adiciones.filter(a => a.id !== id);
                } catch (err) {
                    console.error('Error al eliminar:', err);
                }
            }
        }
    }
</script>
