<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: monospace, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New"; font-size: 11px; line-height: 1.4; }
    .ticket { width: 320px; margin: auto; border: 1px dashed #ccc; padding: 12px; }
    .center { text-align: center; }
    .bold { font-weight: bold; }
    .list { list-style: none; padding: 0; margin: 0; }
    .list li { margin-bottom: 3px; }
    .row { display: flex; justify-content: space-between; }
    .line { border-top: 1px dashed #bbb; margin: 8px 0; }
    .section-title { font-weight: bold; margin-top: 8px; margin-bottom: 4px; text-transform: uppercase; font-size: 10px; }
    .fiscal-box { background: #f9f9f9; padding: 6px; margin: 6px 0; border: 1px solid #ddd; font-size: 10px; }
    .small-text { font-size: 9px; color: #666; }
</style>
</head>
<body>
<div class="ticket">
    {{-- ENCABEZADO --}}
    <div class="center">
        <p class="bold" style="font-size: 14px; margin: 0 0 4px 0;">{{ $orden->restaurante->nombre ?? 'Restaurante' }}</p>
        <p class="bold" style="margin: 0 0 8px 0;">FACTURA SIMPLIFICADA (TICKET)</p>
    </div>

    <div class="line"></div>

    {{-- 📅 DATOS DEL DOCUMENTO (primero) --}}
    <div class="fiscal-box">
        <div class="section-title">📅 DATOS DEL DOCUMENTO</div>
        <div><strong>Tipo:</strong> Factura Simplificada (Ticket)</div>
        <div><strong>Mesa:</strong> {{ $orden->mesa->nombre ?? 'No definida' }}</div>
        <div><strong>Fecha Emisión:</strong> {{ optional($orden->created_at)->format('d/m/Y H:i:s') }}</div>
        @if($orden->factura)
            <div><strong>Nº Factura:</strong> {{ $orden->factura->numero_factura }}</div>
        @endif
    </div>

    {{-- 🏢 DATOS DEL EMISOR (segundo) --}}
    @if($orden->restaurante)
        <div class="fiscal-box">
            <div class="section-title">🏢 DATOS DEL EMISOR</div>

            @if($orden->restaurante->razon_social)
                <div><strong>Razón Social:</strong> {{ $orden->restaurante->razon_social }}</div>
            @endif

            @if($orden->restaurante->nif)
                <div><strong>NIF/CIF:</strong> {{ $orden->restaurante->nif }}</div>
            @endif

            @if($orden->restaurante->direccion_fiscal)
                <div><strong>Domicilio:</strong> {{ $orden->restaurante->direccion_fiscal }}</div>
                @if($orden->restaurante->codigo_postal || $orden->restaurante->municipio)
                    <div style="margin-left: 12px;">
                        @if($orden->restaurante->codigo_postal){{ $orden->restaurante->codigo_postal }}@endif
                        @if($orden->restaurante->municipio) {{ $orden->restaurante->municipio }}@endif
                        @if($orden->restaurante->provincia) ({{ $orden->restaurante->provincia }})@endif
                    </div>
                @endif
            @endif

            @if($orden->restaurante->regimen_iva)
                <div><strong>Régimen Fiscal:</strong>
                    @if($orden->restaurante->regimen_iva === 'general')
                        Régimen General
                    @elseif($orden->restaurante->regimen_iva === 'simplificado')
                        Régimen Simplificado
                    @elseif($orden->restaurante->regimen_iva === 'criterio_caja')
                        Criterio de Caja
                    @else
                        {{ ucfirst($orden->restaurante->regimen_iva) }}
                    @endif
                </div>
            @endif

            @if($orden->restaurante->epigrafe_iae)
                <div><strong>Epígrafe IAE:</strong> {{ $orden->restaurante->epigrafe_iae }}</div>
            @endif
        </div>
    @endif

    <div class="line"></div>

    <div class="bold row">
        <span>Cant.</span>
        <span>Artículo</span>
        <span>Total</span>
    </div>

    <div class="line"></div>

    @php
        $subtotal = 0.0;
    @endphp

    @foreach ((array) $orden->productos as $producto)
        @php
            $precioBase = (float) ($producto['precio_base'] ?? ($producto['precio'] ?? 0));
            $cantidad   = (int)   ($producto['cantidad'] ?? 1);
            $adiciones  = collect($producto['adiciones'] ?? []);
            $adicionesTotal = (float) $adiciones->sum('precio');
            $totalLinea = ($precioBase + $adicionesTotal) * $cantidad;
            $subtotal += $totalLinea;
        @endphp

        <div class="row" style="align-items: flex-start;">
            <span>{{ $cantidad }}</span>
            <span style="flex:1; text-align:center; padding: 0 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                {{ $producto['nombre'] ?? 'Producto' }}
            </span>
            <span>{{ number_format($totalLinea, 2, ',', '.') }}</span>
        </div>

        @if ($adiciones->isNotEmpty())
            <ul class="list" style="margin-left: 18px; color:#666;">
                @foreach ($adiciones as $adic)
                    <li>+ {{ $adic['nombre'] ?? 'Extra' }} (+{{ number_format((float)($adic['precio'] ?? 0), 2, ',', '.') }})</li>
                @endforeach
            </ul>
        @endif
    @endforeach

    <div class="line"></div>

    @php
        $iva = $subtotal * 0.10; // 10%
        $totalConIva = $subtotal + $iva;
    @endphp

    <div class="row">
        <span>Subtotal</span>
        <span>{{ number_format($subtotal, 2, ',', '.') }} €</span>
    </div>
    <div class="row">
        <span>IVA (10%)</span>
        <span>{{ number_format($iva, 2, ',', '.') }} €</span>
    </div>
    <div class="row bold" style="margin-top: 4px;">
        <span>TOTAL</span>
        <span>{{ number_format($totalConIva, 2, ',', '.') }} €</span>
    </div>

    <div class="line"></div>

    {{-- 🧾 INFORMACIÓN VERIFACTU --}}
    @if($orden->factura && $orden->restaurante->fiscal_habilitado)
        <div class="fiscal-box">
            <div class="section-title">🧾 CAMPOS VERIFACTU</div>

            <div class="small-text center" style="margin-bottom: 4px;">
                <em>Factura emitida por sistema VeriFactu</em>
            </div>

            @if($orden->factura->verifactu_id)
                <div><strong>Estado:</strong> ✅ Comunicada a AEAT</div>
                <div><strong>UUID:</strong> {{ Str::limit($orden->factura->verifactu_id, 40, '...') }}</div>

                @if($orden->factura->verifactu_huella)
                    <div><strong>Huella Digital:</strong> {{ Str::limit($orden->factura->verifactu_huella, 40, '...') }}</div>
                @endif

                @if($orden->factura->fecha_envio_verifactu)
                    <div><strong>Timestamp:</strong> {{ $orden->factura->fecha_envio_verifactu->format('d/m/Y H:i:s') }}</div>
                @endif

                {{-- QR Code VeriFactu --}}
                @if($orden->factura->verifactu_qr_data)
                    <div class="center" style="margin-top: 8px;">
                        <div class="small-text" style="margin-bottom: 4px;"><strong>Código QR VeriFactu - AEAT</strong></div>
                        <img src="data:image/png;base64,{{ $orden->factura->verifactu_qr_data }}"
                             alt="QR VeriFactu"
                             style="width: 120px; height: 120px; border: 1px solid #ddd; padding: 4px; background: white;">
                        <div class="small-text" style="margin-top: 4px;">Escanee para verificar autenticidad</div>
                    </div>
                @endif
            @else
                <div><strong>Estado:</strong> ⏳ Pendiente de comunicar</div>
                <div class="small-text">La factura será enviada a VeriFactu/AEAT próximamente</div>
            @endif
        </div>
    @endif

    {{-- MENSAJE DE AGRADECIMIENTO --}}
    <div class="line"></div>
    <p class="center bold" style="margin: 8px 0 4px 0;">¡Gracias por su visita!</p>

    @if($orden->restaurante->email_fiscal || $orden->restaurante->telefono_fiscal)
        <div class="center small-text" style="color: #666;">
            @if($orden->restaurante->email_fiscal)
                <div>{{ $orden->restaurante->email_fiscal }}</div>
            @endif
            @if($orden->restaurante->telefono_fiscal)
                <div>Tel: {{ $orden->restaurante->telefono_fiscal }}</div>
            @endif
        </div>
    @endif
</div>
</body>
</html>
