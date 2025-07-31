@extends('layouts.app')

@section('title', 'Mi perfil')

@section('content')
@php
    // Si el menú público o mesa está activo, el sidebar no se muestra
    $sinSidebar = Request::routeIs(['menu.publico', 'menu.publico.mesa', 'seguimiento', 'cuenta.pedir']);
@endphp

<div class="{{ $sinSidebar ? 'min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8' : 'py-12 px-4 sm:px-6 lg:px-8' }}">
    <div class="w-full max-w-2xl space-y-6 mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Mi perfil</h2>

        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</div>
@endsection
