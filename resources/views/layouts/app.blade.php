!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php($settings = $restaurante->siteSetting ?? null)

<head>
    <meta charset="utf-8">
    {{-- VIEWPORT CRÍTICO PARA MÓVILES --}}
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    
    {{-- Prevenir detección automática de teléfonos --}}
    <meta name="format-detection" content="telephone=no">
    
    {{-- Prevenir zoom en iOS --}}
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $settings->site_name ?? config('app.name', 'FlexFood') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    @if(!empty($settings?->favicon_path))
    <link rel="icon" href="{{ asset($settings->favicon_path) }}">
    <link rel="shortcut icon" href="{{ asset($settings->favicon_path) }}">
    @endif

    {{-- CSS crítico para prevenir problemas de zoom --}}
    <style>
        /* Reset global para móviles */
        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        html {
            width: 100%;
            overflow-x: hidden;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }
        
        body {
            width: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* Prevenir zoom en inputs - CRÍTICO */
        input,
        textarea,
        select,
        button {
            font-size: 16px !important;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }
        
        /* Para iOS específicamente */
        input:focus,
        textarea:focus,
        select:focus {
            font-size: 16px !important;
        }
        
        /* Prevenir overflow horizontal */
        .overflow-x-hidden {
            overflow-x: hidden !important;
        }
        
        /* Contenedor principal */
        #app-wrapper {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
            position: relative;
        }
        
        /* Fix para elementos con width: 100vw */
        [style*="100vw"] {
            width: 100% !important;
            max-width: 100% !important;
        }
        
        /* Prevenir scroll horizontal en móviles */
        @media (max-width: 768px) {
            .flex.min-h-screen {
                width: 100%;
                max-width: 100%;
                overflow-x: hidden;
            }
            
            main {
                width: 100%;
                max-width: 100%;
                overflow-x: hidden;
            }
        }
    </style>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-gray-100 overflow-x-hidden">
    <div id="app-wrapper">
        <div class="flex min-h-screen">

            {{-- Sidebar (excepto en menú público y menú con mesa) --}}
            @unless (Request::routeIs(['menu.publico', 'menu.publico.mesa', 'seguimiento', 'cuenta.pedir']))
            @include('layouts.navigation')
            @endunless

            {{-- Contenido principal --}}
            <div class="flex-1 flex flex-col overflow-x-hidden">

                {{-- Contenido --}}
                <main class="flex-1 p-4 overflow-x-hidden">
                    @yield('content')
                </main>

            </div>
        </div>
    </div>

    {{-- Scripts --}}
    <script src="//unpkg.com/alpinejs" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    {{-- Script adicional para prevenir zoom por doble tap --}}
    <script>
        // Prevenir zoom por doble tap en iOS
        document.addEventListener('gesturestart', function(e) {
            e.preventDefault();
        });
        
        // Prevenir zoom con gestos
        document.addEventListener('touchmove', function(event) {
            if (event.scale !== 1) {
                event.preventDefault();
            }
        }, { passive: false });
        
        // Fix para viewport en iOS
        if (/iPhone|iPad|iPod/.test(navigator.userAgent)) {
            document.querySelector('meta[name="viewport"]').setAttribute('content', 
                'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
        }
    </script>

    @stack('scripts')
</body>

</html>