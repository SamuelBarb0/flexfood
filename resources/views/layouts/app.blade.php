<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php($settings = $restaurante->siteSetting ?? null)

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $settings->site_name ?? config('app.name', 'FlexFood') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/png"
      href="{{ isset($settings->favicon_path) ? asset($settings->favicon_path) : asset('images/favicon.png') }}">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-gray-100">
    <div class="flex min-h-screen">

        {{-- Sidebar (excepto en menú público y menú con mesa) --}}
        @unless (Request::routeIs(['menu.publico', 'menu.publico.mesa', 'seguimiento', 'cuenta.pedir']))
        @include('layouts.navigation')
        @endunless

        {{-- Contenido principal --}}
        <div class="flex-1 flex flex-col">

            {{-- Contenido --}}
            <main class="flex-1 p-4">
                @yield('content')
            </main>

        </div>
    </div>

    <script src="//unpkg.com/alpinejs" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @stack('scripts')


</body>

</html>