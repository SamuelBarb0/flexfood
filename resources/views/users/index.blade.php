@extends('layouts.app')

@section('content')
<div class="container" x-data="{ openEdit: null, openDelete: null, openCreate: false }">
    <h2>Usuarios</h2>
    <button @click="openCreate = true" class="btn btn-primary mb-3">Nuevo Usuario</button>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>Nombre</th><th>Email</th><th>Rol</th><th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        @foreach($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->roles->pluck('name')->first() }}</td>
                <td>
                    <button @click="openEdit = {{ $user->id }}" class="btn btn-sm btn-warning">Editar</button>
                    <button @click="openDelete = {{ $user->id }}" class="btn btn-sm btn-danger">Eliminar</button>
                </td>
            </tr>

            @include('users.partials.edit', ['user' => $user, 'roles' => $roles])
            @include('users.partials.delete', ['user' => $user])
            @include('users.partials.create', ['roles' => $roles])
        @endforeach
        </tbody>
    </table>
</div>
@endsection
