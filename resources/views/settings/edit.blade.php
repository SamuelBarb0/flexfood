{{-- resources/views/admin/settings/edit.blade.php --}}
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

        {{-- Configuración de Tickets --}}
        <div class="mt-8 pt-8 border-t">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">📄 Configuración de Tickets</h2>
            <p class="text-sm text-gray-600 mb-6">Personaliza la información que aparecerá en los tickets impresos y enviados por email.</p>

            {{-- Información de Cabecera --}}
            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Información de Cabecera</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Nombre del Negocio</label>
                        <input
                            type="text"
                            name="ticket_config[header][business_name]"
                            value="{{ old('ticket_config.header.business_name', ($settings->ticket_config['header']['business_name'] ?? $restaurante->nombre)) }}"
                            class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0 text-sm"
                            placeholder="Ej. Mi Restaurante S.L."
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">CIF/NIF</label>
                        <input
                            type="text"
                            name="ticket_config[header][cif]"
                            value="{{ old('ticket_config.header.cif', ($settings->ticket_config['header']['cif'] ?? '')) }}"
                            class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0 text-sm"
                            placeholder="Ej. B12345678"
                        >
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Dirección</label>
                    <input
                        type="text"
                        name="ticket_config[header][address]"
                        value="{{ old('ticket_config.header.address', ($settings->ticket_config['header']['address'] ?? '')) }}"
                        class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0 text-sm"
                        placeholder="Ej. Calle Principal 123, 28001 Madrid"
                    >
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Teléfono</label>
                        <input
                            type="text"
                            name="ticket_config[header][phone]"
                            value="{{ old('ticket_config.header.phone', ($settings->ticket_config['header']['phone'] ?? '')) }}"
                            class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0 text-sm"
                            placeholder="Ej. 912 345 678"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Email</label>
                        <input
                            type="email"
                            name="ticket_config[header][email]"
                            value="{{ old('ticket_config.header.email', ($settings->ticket_config['header']['email'] ?? '')) }}"
                            class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0 text-sm"
                            placeholder="Ej. info@mirestaurante.com"
                        >
                    </div>
                </div>
                <div class="mt-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            name="ticket_config[header][show_logo]"
                            value="1"
                            {{ old('ticket_config.header.show_logo', ($settings->ticket_config['header']['show_logo'] ?? true)) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-[#153958] focus:ring-0"
                        >
                        <span class="text-xs text-gray-700">Mostrar logo en el ticket (si existe)</span>
                    </label>
                </div>
            </div>

            {{-- Pie de Ticket --}}
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Pie de Ticket</h3>
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Mensaje de Agradecimiento</label>
                    <input
                        type="text"
                        name="ticket_config[footer][thank_you_message]"
                        value="{{ old('ticket_config.footer.thank_you_message', ($settings->ticket_config['footer']['thank_you_message'] ?? '¡Gracias por su visita!')) }}"
                        class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0 text-sm"
                        placeholder="Ej. ¡Gracias por su visita!"
                    >
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Texto Personalizado / Aviso Legal</label>
                    <textarea
                        name="ticket_config[footer][custom_text]"
                        rows="3"
                        class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0 text-sm"
                        placeholder="Ej. IVA incluido. Conserve este ticket para cualquier reclamación."
                    >{{ old('ticket_config.footer.custom_text', ($settings->ticket_config['footer']['custom_text'] ?? '')) }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Sitio Web</label>
                    <input
                        type="text"
                        name="ticket_config[footer][website]"
                        value="{{ old('ticket_config.footer.website', ($settings->ticket_config['footer']['website'] ?? '')) }}"
                        class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0 text-sm"
                        placeholder="Ej. www.mirestaurante.com"
                    >
                </div>
            </div>

            <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                <p class="text-xs text-blue-800">
                    💡 <strong>Nota:</strong> El IVA (10%) se calcula automáticamente y no se puede modificar desde aquí. Si necesitas cambiar el porcentaje de IVA, contacta con soporte.
                </p>
            </div>
        </div>

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

@push('scripts')
<script>
    function testNotificationSound() {
        const soundPath = "{{ !empty($restaurante->notification_sound_path) ? asset($restaurante->notification_sound_path) : '' }}";
        if (soundPath) {
            const audio = new Audio(soundPath);
            audio.play().catch(err => console.error('Error al reproducir:', err));
        } else {
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
@endpush
