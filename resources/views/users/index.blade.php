@extends('layouts.app')

@section('content')
@php($restaurante = $restaurante ?? request()->route('restaurante'))

<div class="container mx-auto px-6 py-8" x-data="{ openEdit: null, openDelete: null, openCreate: false }">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-[#153958]">Gestión de Usuarios</h2>
        <button @click="openCreate = true"
                class="bg-[#3CB28B] text-white px-4 py-2 rounded hover:bg-[#319c78] transition">
            + Nuevo Usuario
        </button>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded border border-green-300">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white shadow rounded-lg">
            <thead class="bg-[#153958] text-white">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-medium uppercase tracking-wider">Nombre</th>
                    <th class="px-6 py-3 text-left text-sm font-medium uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-sm font-medium uppercase tracking-wider">Rol</th>
                    <th class="px-6 py-3 text-left text-sm font-medium uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($users as $user)
                    <tr class="hover:bg-[#F2F2F2]">
                        <td class="px-6 py-4">{{ $user->name }}</td>
                        <td class="px-6 py-4">{{ $user->email }}</td>
                        <td class="px-6 py-4 text-[#153958] font-semibold">
                            {{ $user->roles->pluck('name')->first() }}
                        </td>
                        <td class="px-6 py-4 space-x-2">
                            <button
                                @click="openEdit = {{ $user->id }}; openDelete = null"
                                class="text-sm bg-yellow-400 hover:bg-yellow-500 text-white px-3 py-1 rounded transition">
                                Editar
                            </button>
                            <button
                                @click="openDelete = {{ $user->id }}; openEdit = null"
                                class="text-sm bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded transition">
                                Eliminar
                            </button>
                        </td>
                    </tr>

                    @include('users.partials.edit', ['user' => $user, 'roles' => $roles, 'restaurante' => $restaurante])
                    @include('users.partials.delete', ['user' => $user, 'restaurante' => $restaurante])
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Modal de Crear --}}
    @include('users.partials.create', ['roles' => $roles, 'restaurante' => $restaurante])
</div>
@endsection
