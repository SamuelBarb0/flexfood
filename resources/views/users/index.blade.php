@extends('layouts.app') 

@section('content')
@php
    $restaurante      = $restaurante ?? request()->route('restaurante');
    $maxPerfiles      = $maxPerfiles ?? null;
    $perfilesActuales = $perfilesActuales ?? 0;
    $limitFull        = (!is_null($maxPerfiles) && $perfilesActuales >= $maxPerfiles);
@endphp

<div class="container mx-auto px-6 py-8" x-data="{ openEdit: null, openDelete: null, openCreate: false }">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <h2 class="text-2xl font-bold text-[#153958]">Gestión de Usuarios</h2>

        <div class="flex items-center gap-3">
            @if(!is_null($maxPerfiles))
                <div class="text-sm px-3 py-2 rounded border bg-yellow-50 border-yellow-200 text-yellow-800">
                    Perfiles (cocina + cajero): <strong>{{ $perfilesActuales }} / {{ $maxPerfiles }}</strong>
                </div>
            @endif

            <button
                @click="openCreate = true"
                @disabled($limitFull)
                title="{{ $limitFull ? 'Has alcanzado el límite de perfiles del plan' : '' }}"
                class="px-4 py-2 rounded transition {{ $limitFull
                    ? 'bg-gray-300 text-gray-600 cursor-not-allowed'
                    : 'bg-[#3CB28B] text-white hover:bg-[#319c78]' }}">
                + Nuevo Usuario
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded border border-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded border border-red-300">
            {{ session('error') }}
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

                    @include('users.partials.edit', [
                        'user' => $user,
                        'roles' => $roles,
                        'restaurante' => $restaurante,
                        'maxPerfiles' => $maxPerfiles,
                        'perfilesActuales' => $perfilesActuales
                    ])

                    @include('users.partials.delete', ['user' => $user, 'restaurante' => $restaurante])
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Modal de Crear --}}
    @include('users.partials.create', [
        'roles' => $roles,
        'restaurante' => $restaurante,
        'maxPerfiles' => $maxPerfiles,
        'perfilesActuales' => $perfilesActuales
    ])
</div>
@endsection
