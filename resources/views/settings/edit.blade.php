{{-- resources/views/admin/settings/edit.blade.php --}}
@php($settings = $restaurante->siteSetting ?? null)

@extends('layouts.app')

@section('title', 'Configuración · ' . $restaurante->nombre)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">
            Configuración — {{ $restaurante->nombre }}
        </h1>
        <p class="text-sm text-gray-500">Actualiza el título del sitio, logo y favicon de este restaurante.</p>
    </div>

    @if(session('ok'))
        <div class="mb-4 rounded-md bg-green-50 p-3 text-green-700 text-sm">
            {{ session('ok') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 p-3 text-red-700 text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('settings.update', $restaurante) }}" method="POST" enctype="multipart/form-data" class="bg-white shadow-sm rounded-xl p-6 border">
        @csrf

        {{-- Título del sitio --}}
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Título del sitio</label>
            <input
                type="text"
                name="site_name"
                value="{{ old('site_name', $settings->site_name ?? config('app.name','FlexFood')) }}"
                class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                placeholder="Ej. FlexFood"
                required
            >
            <p class="text-xs text-gray-500 mt-1">Se usa en la etiqueta &lt;title&gt; del navegador.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Logo --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Logo</label>
                <div class="flex items-center gap-4">
                    <div class="h-16 w-16 rounded-lg border bg-gray-50 flex items-center justify-center overflow-hidden">
                        @if(!empty($settings?->logo_path))
                            <img src="{{ asset($settings->logo_path) }}" alt="Logo actual" class="h-full object-contain">
                        @else
                            <span class="text-xs text-gray-400">Sin logo</span>
                        @endif
                    </div>
                    <div class="flex-1">
                        <input
                            type="file"
                            name="logo_path"
                            accept="image/png,image/jpeg,image/webp"
                            class="block w-full text-sm text-gray-700 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 hover:file:bg-gray-200"
                        />
                        <p class="text-xs text-gray-500 mt-1">
                            PNG/JPG/WEBP. Recomendado: fondo transparente, altura ~96px. Máx 2MB.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Favicon --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Favicon</label>
                <div class="flex items-center gap-4">
                    <div class="h-10 w-10 rounded-lg border bg-gray-50 flex items-center justify-center overflow-hidden">
                        @if(!empty($settings?->favicon_path))
                            <img src="{{ asset($settings->favicon_path) }}" alt="Favicon actual" class="h-full w-full object-contain">
                        @else
                            <span class="text-[10px] text-gray-400 px-1 text-center leading-tight">Sin<br>favicon</span>
                        @endif
                    </div>
                    <div class="flex-1">
                        <input
                            type="file"
                            name="favicon_path"
                            accept="image/png,image/x-icon"
                            class="block w-full text-sm text-gray-700 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 hover:file:bg-gray-200"
                        />
                        <p class="text-xs text-gray-500 mt-1">PNG o ICO. Recomendado: 32×32 o 48×48. Máx 512KB.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sonido de Notificación --}}
        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Sonido de Notificación</label>
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    @if(!empty($restaurante->notification_sound_path))
                        <div class="mb-2 flex items-center gap-2">
                            <span class="text-sm text-gray-600">Sonido actual:</span>
                            <button type="button" onclick="testNotificationSound()" class="text-sm text-[#153958] hover:underline">
                                🔊 Reproducir
                            </button>
                        </div>
                    @endif
                    <input
                        type="file"
                        name="notification_sound"
                        accept="audio/mpeg,audio/wav,audio/ogg"
                        class="block w-full text-sm text-gray-700 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 hover:file:bg-gray-200"
                    />
                    <p class="text-xs text-gray-500 mt-1">
                        MP3, WAV u OGG. Este sonido se reproducirá cuando llegue un nuevo pedido. Máx 1MB.
                        @if(empty($restaurante->notification_sound_path))
                            <br><strong>Sin sonido:</strong> Se usará un beep predeterminado.
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <script>
            function testNotificationSound() {
                const soundPath = "{{ !empty($restaurante->notification_sound_path) ? asset($restaurante->notification_sound_path) : '' }}";
                if (soundPath) {
                    const audio = new Audio(soundPath);
                    audio.play().catch(err => console.error('Error al reproducir:', err));
                } else {
                    // Beep predeterminado
                    playDefaultBeep();
                }
            }

            function playDefaultBeep() {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);

                oscillator.frequency.value = 800;
                oscillator.type = 'sine';

                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.5);
            }
        </script>

        <div class="mt-8 flex items-center justify-end gap-3">
            <a href="{{ route('dashboard', $restaurante) }}" class="text-sm text-gray-600 hover:text-gray-800">Cancelar</a>
            <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-[#153958] text-white text-sm hover:opacity-95">
                Guardar cambios
            </button>
        </div>
    </form>
</div>
@endsection
