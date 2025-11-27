{{-- resources/views/settings/partials/fiscal-config.blade.php --}}

<div class="space-y-6">
    {{-- Información sobre VeriFacti --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-medium text-blue-800">Sobre VeriFacti</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>VeriFacti es una plataforma SaaS que gestiona la facturación electrónica cumpliendo con Veri*Factu de la AEAT. Para habilitar la facturación en FlexFood, configura tus credenciales de VeriFacti y completa los datos fiscales.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Estado de habilitación fiscal --}}
    @if($restaurante->fiscal_habilitado)
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="ml-3 text-sm font-medium text-green-800">
                    Facturación habilitada desde el {{ $restaurante->fiscal_habilitado_at?->format('d/m/Y H:i') }}
                </span>
            </div>
        </div>
    @endif

    {{-- Formulario de datos fiscales --}}
    <form action="{{ route('fiscal.update', $restaurante) }}" method="POST" enctype="multipart/form-data" class="bg-white shadow-sm rounded-xl p-6 border">
        @csrf

        <h2 class="text-lg font-semibold text-gray-800 mb-6">Datos Fiscales del Restaurante</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Razón Social --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Razón Social <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    name="razon_social"
                    value="{{ old('razon_social', $restaurante->razon_social) }}"
                    class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                    placeholder="Ej. Mi Restaurante S.L."
                    required
                >
                <p class="text-xs text-gray-500 mt-1">Nombre legal completo de la empresa</p>
            </div>

            {{-- Nombre Comercial --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Comercial</label>
                <input
                    type="text"
                    name="nombre_comercial"
                    value="{{ old('nombre_comercial', $restaurante->nombre_comercial ?? $restaurante->nombre) }}"
                    class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                    placeholder="Ej. {{ $restaurante->nombre }}"
                >
            </div>

            {{-- NIF --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    NIF/CIF <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    name="nif"
                    value="{{ old('nif', $restaurante->nif) }}"
                    class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                    placeholder="Ej. B12345678"
                    maxlength="9"
                    required
                    x-data="{ value: '{{ old('nif', $restaurante->nif) }}' }"
                    x-model="value"
                    @input="value = value.toUpperCase()"
                >
                <p class="text-xs text-gray-500 mt-1">Se validará automáticamente</p>
            </div>
        </div>

        {{-- Dirección Fiscal --}}
        <div class="mt-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Dirección Fiscal</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Dirección completa <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="direccion_fiscal"
                        value="{{ old('direccion_fiscal', $restaurante->direccion_fiscal) }}"
                        class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                        placeholder="Ej. Calle Principal 123, 3º B"
                        required
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Municipio <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="municipio"
                        value="{{ old('municipio', $restaurante->municipio) }}"
                        class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                        placeholder="Ej. Madrid"
                        required
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Provincia <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="provincia"
                        value="{{ old('provincia', $restaurante->provincia) }}"
                        class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                        placeholder="Ej. Madrid"
                        required
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Código Postal <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="codigo_postal"
                        value="{{ old('codigo_postal', $restaurante->codigo_postal) }}"
                        class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                        placeholder="Ej. 28001"
                        maxlength="5"
                        required
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        País <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="pais"
                        value="{{ old('pais', $restaurante->pais ?? 'España') }}"
                        class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                        required
                    >
                </div>
            </div>
        </div>

        {{-- Información Fiscal --}}
        <div class="mt-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Información Fiscal</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Régimen de IVA <span class="text-red-500">*</span>
                    </label>
                    <select
                        name="regimen_iva"
                        class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                        required
                    >
                        <option value="">Seleccionar...</option>
                        <option value="general" {{ old('regimen_iva', $restaurante->regimen_iva) === 'general' ? 'selected' : '' }}>
                            Régimen General
                        </option>
                        <option value="simplificado" {{ old('regimen_iva', $restaurante->regimen_iva) === 'simplificado' ? 'selected' : '' }}>
                            Régimen Simplificado
                        </option>
                        <option value="criterio_caja" {{ old('regimen_iva', $restaurante->regimen_iva) === 'criterio_caja' ? 'selected' : '' }}>
                            Criterio de Caja
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Epígrafe IAE</label>
                    <input
                        type="text"
                        name="epigrafe_iae"
                        value="{{ old('epigrafe_iae', $restaurante->epigrafe_iae) }}"
                        class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                        placeholder="Ej. 671"
                    >
                    <p class="text-xs text-gray-500 mt-1">Restaurantes suelen usar el 671</p>
                </div>
            </div>
        </div>

        {{-- Contacto Fiscal --}}
        <div class="mt-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Contacto Fiscal</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Fiscal</label>
                    <input
                        type="email"
                        name="email_fiscal"
                        value="{{ old('email_fiscal', $restaurante->email_fiscal) }}"
                        class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                        placeholder="Ej. facturacion@mirestaurante.com"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono Fiscal</label>
                    <input
                        type="text"
                        name="telefono_fiscal"
                        value="{{ old('telefono_fiscal', $restaurante->telefono_fiscal) }}"
                        class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                        placeholder="Ej. 912 345 678"
                    >
                </div>
            </div>
        </div>

        <div class="mt-8 flex items-center justify-end gap-3">
            <a href="{{ route('dashboard', $restaurante) }}" class="text-sm text-gray-600 hover:text-gray-800">Cancelar</a>
            <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-[#153958] text-white text-sm hover:opacity-95">
                Guardar datos fiscales
            </button>
        </div>
    </form>

    {{-- Credenciales VeriFacti API --}}
    <div class="bg-white shadow-sm rounded-xl p-6 border">
        <h2 class="text-lg font-semibold text-gray-800 mb-2">Credenciales VeriFacti API</h2>
        <p class="text-sm text-gray-600 mb-6">
            Para enviar facturas a la AEAT necesitas credenciales de VeriFacti.
            Si aún no tienes cuenta,
            <a href="https://www.verifacti.com/" target="_blank" class="text-blue-600 hover:underline font-medium">
                regístrate aquí →
            </a>
        </p>

        @php
            $tieneCredenciales = $restaurante->tieneCredencialesVeriFactu();
        @endphp

        @if($tieneCredenciales)
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-green-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium text-green-800">Credenciales configuradas</h3>
                        <p class="mt-1 text-sm text-green-700">
                            Usuario: <strong>{{ $restaurante->verifactu_api_username }}</strong>
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('fiscal.credenciales.update', $restaurante) }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Usuario (NIF) <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="verifactu_api_username"
                        value="{{ old('verifactu_api_username', $restaurante->verifactu_api_username) }}"
                        class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                        placeholder="Ej. {{ $restaurante->nif ?? 'B12345678' }}"
                        maxlength="9"
                        required
                    >
                    <p class="text-xs text-gray-500 mt-1">Generalmente es el mismo NIF del restaurante</p>
                    @error('verifactu_api_username')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        API Key <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="password"
                        name="verifactu_api_key"
                        class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                        placeholder="••••••••••••••••••••••••"
                        {{ $tieneCredenciales ? '' : 'required' }}
                    >
                    <p class="text-xs text-gray-500 mt-1">
                        @if($tieneCredenciales)
                            Dejar en blanco para mantener la actual
                        @else
                            Obtenla desde tu panel de VeriFacti
                        @endif
                    </p>
                    @error('verifactu_api_key')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end">
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 rounded-lg bg-[#153958] text-white text-sm hover:opacity-95">
                    @if($tieneCredenciales)
                        Actualizar credenciales
                    @else
                        Guardar credenciales
                    @endif
                </button>
            </div>
        </form>
    </div>

    {{-- Certificado Digital --}}
    <div class="bg-white shadow-sm rounded-xl p-6 border">
        <h2 class="text-lg font-semibold text-gray-800 mb-2">Certificado Digital (Opcional)</h2>
        <p class="text-sm text-gray-600 mb-6">
            Con VeriFacti, el certificado digital es opcional ya que VeriFacti usa su propio certificado para firmar las facturas.
            Solo necesitas subirlo si quieres firmar con tu propio certificado.
        </p>

        @php
            $certificadoActivo = $restaurante->certificadoActivo;
        @endphp

        @if($certificadoActivo)
            {{-- Mostrar certificado actual --}}
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-green-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium text-green-800">Certificado Válido</h3>
                        <div class="mt-2 text-sm text-green-700 space-y-1">
                            <p><strong>Titular:</strong> {{ $certificadoActivo->titular_certificado }}</p>
                            <p><strong>NIF:</strong> {{ $certificadoActivo->nif_certificado }}</p>
                            <p><strong>Válido hasta:</strong> {{ $certificadoActivo->fecha_caducidad->format('d/m/Y') }}</p>
                            @if($certificadoActivo->proximoACaducar())
                                <p class="text-orange-700 font-medium">⚠️ El certificado caduca pronto. Renuévalo cuanto antes.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- No hay certificado --}}
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <p class="ml-3 text-sm text-yellow-700">
                        <strong>No hay certificado digital.</strong> Debes subir tu certificado digital para poder emitir facturas.
                    </p>
                </div>
            </div>
        @endif

        {{-- Formulario para subir certificado --}}
        <form action="{{ route('fiscal.certificado.upload', $restaurante) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Archivo del Certificado <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="file"
                        name="certificado"
                        accept=".p12,.pfx,application/x-pkcs12"
                        class="block w-full text-sm text-gray-700 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 hover:file:bg-gray-200"
                        required
                    />
                    <p class="text-xs text-gray-500 mt-1">Formato .p12 o .pfx. Máximo 5MB.</p>
                    @error('certificado')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Contraseña del Certificado <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="password"
                        name="password_certificado"
                        class="w-full rounded-lg border-gray-300 focus:border-gray-400 focus:ring-0"
                        placeholder="Contraseña del certificado"
                        required
                    >
                    <p class="text-xs text-gray-500 mt-1">Se almacenará de forma encriptada</p>
                    @error('password_certificado')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end">
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 rounded-lg bg-[#153958] text-white text-sm hover:opacity-95">
                    @if($certificadoActivo)
                        Reemplazar certificado
                    @else
                        Subir certificado
                    @endif
                </button>
            </div>
        </form>
    </div>

    {{-- Series de Facturación --}}
    <div class="bg-white shadow-sm rounded-xl p-6 border">
        <h2 class="text-lg font-semibold text-gray-800 mb-2">Series de Facturación</h2>
        <p class="text-sm text-gray-600 mb-6">Configura las series de numeración para tus facturas</p>

        @php
            $series = $restaurante->seriesFacturacion;
            $seriePrincipal = $restaurante->seriePrincipal;
        @endphp

        @if($series->isNotEmpty())
            <div class="space-y-3 mb-6">
                @foreach($series as $serie)
                    <div class="flex items-center justify-between p-4 border rounded-lg {{ $serie->es_principal ? 'border-[#153958] bg-blue-50' : 'border-gray-200' }}">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-medium text-gray-900">{{ $serie->codigo_serie }}</h3>
                                @if($serie->es_principal)
                                    <span class="px-2 py-0.5 text-xs font-medium bg-[#153958] text-white rounded">Principal</span>
                                @endif
                                @if(!$serie->activa)
                                    <span class="px-2 py-0.5 text-xs font-medium bg-gray-200 text-gray-600 rounded">Inactiva</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600 mt-1">{{ $serie->nombre ?? $serie->descripcion }}</p>
                            <p class="text-xs text-gray-500 mt-1">
                                Próximo número: <strong>{{ $serie->previewSiguienteNumero() }}</strong>
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('fiscal.serie.edit', [$restaurante, $serie]) }}"
                               class="text-sm text-[#153958] hover:underline">
                                Editar
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-yellow-700">
                    <strong>No hay series de facturación.</strong> Crea al menos una serie para empezar a facturar.
                </p>
            </div>
        @endif

        <div class="flex justify-end">
            <a href="{{ route('fiscal.serie.create', $restaurante) }}"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-[#153958] text-white text-sm hover:opacity-95">
                + Nueva serie
            </a>
        </div>
    </div>

    {{-- Configuración de Facturación Automática --}}
    @if($restaurante->fiscal_habilitado)
        <div class="bg-white shadow-sm rounded-xl p-6 border">
            <h2 class="text-lg font-semibold text-gray-800 mb-2">Facturación Automática</h2>
            <p class="text-sm text-gray-600 mb-6">
                Cuando está habilitada, se genera y envía automáticamente una factura a VeriFacti cada vez que se finaliza (paga) un pedido.
            </p>

            <form action="{{ route('fiscal.update', $restaurante) }}" method="POST">
                @csrf

                <div class="flex items-center justify-between p-4 border rounded-lg">
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-gray-900">Generar facturas automáticamente al finalizar pedidos</h3>
                        <p class="text-xs text-gray-500 mt-1">
                            Las facturas se crearán, emitirán y enviarán a VeriFacti automáticamente cuando se marque un pedido como pagado.
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer ml-4">
                        <input type="checkbox" name="facturacion_automatica" value="1"
                               {{ $restaurante->facturacion_automatica ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#153958]"></div>
                    </label>
                </div>

                <div class="mt-4 flex items-center justify-end">
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 rounded-lg bg-[#153958] text-white text-sm hover:opacity-95">
                        Guardar configuración
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Botón de habilitación --}}
    @php
        $datosFiscalesOk = $restaurante->datosFiscalesCompletos();
        $credencialesOk = $restaurante->tieneCredencialesVeriFactu();
        $serieOk = $restaurante->seriePrincipal()->exists();
        $todoCompleto = $datosFiscalesOk && $credencialesOk && $serieOk;
    @endphp

    @if(!$restaurante->fiscal_habilitado)
        <div class="bg-white shadow-sm rounded-xl p-6 border">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Habilitar Facturación VeriFacti</h3>

            @if($todoCompleto)
                <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                    <p class="text-sm text-green-700">
                        ✓ Todos los requisitos están completos. Ya puedes habilitar la facturación electrónica con VeriFacti.
                    </p>
                </div>
                <form action="{{ route('fiscal.habilitar', $restaurante) }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center px-6 py-3 rounded-lg bg-green-600 text-white font-medium hover:bg-green-700">
                        ✓ Habilitar Facturación VeriFacti
                    </button>
                </form>
            @else
                <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-sm text-yellow-800 font-medium mb-2">Requisitos pendientes:</p>
                    <ul class="text-sm text-yellow-700 space-y-1 ml-4 list-disc">
                        @if(!$datosFiscalesOk)
                            <li>Completar datos fiscales del restaurante</li>
                        @endif
                        @if(!$credencialesOk)
                            <li>Configurar credenciales de VeriFacti API</li>
                        @endif
                        @if(!$serieOk)
                            <li>Configurar al menos una serie de facturación</li>
                        @endif
                    </ul>
                    <p class="text-xs text-gray-600 mt-3">
                        Nota: Con VeriFacti, el certificado digital es opcional. VeriFacti firma las facturas con su propio certificado.
                    </p>
                </div>
                <button type="button" disabled
                    class="inline-flex items-center px-6 py-3 rounded-lg bg-gray-300 text-gray-500 font-medium cursor-not-allowed">
                    Habilitar Facturación VeriFacti
                </button>
            @endif
        </div>
    @endif
</div>
