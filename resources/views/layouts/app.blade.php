<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/png" href="{{ asset('images/flexfood.png') }}">




    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-gray-100">
    <div class="flex min-h-screen">

        {{-- Sidebar (excepto en menú público y menú con mesa) --}}
        @unless (Request::routeIs(['menu.publico', 'menu.publico.mesa']))
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


</body>

</html>