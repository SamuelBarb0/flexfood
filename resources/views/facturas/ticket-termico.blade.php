<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket {{ $factura->numero_factura }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        @page {
            size: 80mm auto;
            margin: 0;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
                width: 80mm;
            }
            @page { margin: 0; }
        }
        body {
            font-family: 'Courier New', 'Consolas', monospace;
            width: 80mm;
            max-width: 80mm;
            margin: 0 auto;
            padding: 5mm;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #fff;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .header {
            text-align: center;
            margin-bottom: 8px;
            border-bottom: 2px dashed #000;
            padding-bottom: 8px;
        }
        .header h1 {
            margin: 0 0 6px 0;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header div {
            margin: 2px 0;
            font-size: 11px;
        }
        .line {
            border-top: 1px dashed #000;
            margin: 8px 0;
            height: 0;
        }
        .double-line {
            border-top: 2px solid #000;
            margin: 8px 0;
            height: 0;
        }
        .item {
            margin: 6px 0;
            page-break-inside: avoid;
        }
        .item-name {
            margin-bottom: 2px;
            font-weight: bold;
            font-size: 12px;
        }
        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-top: 2px;
        }
        .addon {
            margin-left: 15px;
            font-size: 10px;
            color: #333;
            font-style: italic;
        }
        .totals {
            margin-top: 12px;
            border-top: 2px solid #000;
            padding-top: 8px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 4px 0;
            font-size: 12px;
        }
        .final-total {
            font-size: 16px;
            font-weight: bold;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 2px double #000;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 10px;
            border-top: 2px dashed #000;
            padding-top: 10px;
        }
        .footer div {
            margin: 3px 0;
        }
        .fiscal-box {
            background: #f9f9f9;
            padding: 4px 6px;
            margin: 6px 0;
            border: 1px solid #ddd;
            font-size: 10px;
            page-break-inside: avoid;
        }
        .fiscal-box-cliente {
            background: #fff3cd;
            padding: 4px 6px;
            margin: 6px 0;
            border: 1px solid #ffc107;
            border-left: 3px solid #ffc107;
            font-size: 10px;
            page-break-inside: avoid;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
        }
        .fiscal-box div,
        .fiscal-box-cliente div {
            margin: 2px 0;
            line-height: 1.3;
        }
        .fiscal-box .indent,
        .fiscal-box-cliente .indent {
            margin-left: 10px;
            font-size: 9px;
        }
        .small-text {
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>
    {{-- Cabecera --}}
    <div class="header">
        @if($restaurante->siteSetting && $restaurante->siteSetting->logo_path)
            <img src="{{ public_path($restaurante->siteSetting->logo_path) }}" alt="Logo" style="max-width: 120px; max-height: 60px; margin: 0 auto 8px auto; display: block;">
        @endif

        <h1>{{ $restaurante->nombre }}</h1>

        @if($restaurante->nif)
            <div>CIF: {{ $restaurante->nif }}</div>
        @endif
        @if($restaurante->direccion_fiscal)
            <div>{{ $restaurante->direccion_fiscal }}</div>
        @endif
        @if($restaurante->codigo_postal && $restaurante->municipio)
            <div>{{ $restaurante->codigo_postal }} {{ $restaurante->municipio }}</div>
        @endif
        @if($restaurante->telefono_fiscal)
            <div>Tel: {{ $restaurante->telefono_fiscal }}</div>
        @endif
        @if($restaurante->email_fiscal)
            <div>{{ $restaurante->email_fiscal }}</div>
        @endif

        @if($factura->orden && $factura->orden->mesa)
            <div>Mesa: {{ $factura->orden->mesa->numero }}</div>
        @endif
        <div>{{ \Carbon\Carbon::parse($factura->fecha_emision)->format('d/m/Y H:i:s') }}</div>
    </div>

    {{-- DATOS DEL DOCUMENTO --}}
    <div class="fiscal-box">
        <div class="section-title">DATOS DEL DOCUMENTO</div>
        <div><strong>Tipo:</strong>
            @if($factura->tipo_factura === 'F1')
                Factura Completa
            @else
                Factura Simplificada
            @endif
        </div>
        @if($factura->orden && $factura->orden->mesa)
            <div><strong>Mesa:</strong> {{ $factura->orden->mesa->numero }}</div>
        @endif
        <div><strong>Fecha Emisión:</strong> {{ \Carbon\Carbon::parse($factura->fecha_emision)->format('d/m/Y H:i:s') }}</div>
        <div><strong>Nº Factura:</strong> {{ $factura->numero_factura }}</div>
        @if($factura->serieFacturacion)
            <div><strong>Serie:</strong> {{ $factura->serieFacturacion->codigo_serie }}</div>
        @endif
    </div>

    {{-- DATOS DEL EMISOR --}}
    @if($restaurante->razon_social || $restaurante->nif || $restaurante->direccion_fiscal)
    <div class="fiscal-box">
        <div class="section-title">DATOS DEL EMISOR</div>

        @if($restaurante->razon_social)
            <div><strong>Razón Social:</strong> {{ $restaurante->razon_social }}</div>
        @endif

        @if($restaurante->nif)
            <div><strong>NIF/CIF:</strong> {{ $restaurante->nif }}</div>
        @endif

        @if($restaurante->direccion_fiscal)
            <div><strong>Domicilio:</strong> {{ $restaurante->direccion_fiscal }}</div>

            @if($restaurante->codigo_postal || $restaurante->municipio)
                <div class="indent">
                    @if($restaurante->codigo_postal){{ $restaurante->codigo_postal }}@endif
                    @if($restaurante->municipio) {{ $restaurante->municipio }}@endif
                    @if($restaurante->provincia) ({{ $restaurante->provincia }})@endif
                </div>
            @endif
        @endif

        @if($restaurante->regimen_fiscal)
            <div><strong>Régimen Fiscal:</strong> {{ $restaurante->regimen_fiscal }}</div>
        @endif
    </div>
    @endif

    {{-- DATOS DEL CLIENTE / DESTINATARIO (solo para facturas F1) --}}
    @if($factura->tipo_factura === 'F1' && $factura->comercioFiscal)
    <div class="fiscal-box-cliente">
        <div class="section-title" style="color: #856404;">DATOS DEL CLIENTE</div>

        <div><strong>Razón Social:</strong> {{ $factura->comercioFiscal->razon_social }}</div>
        <div><strong>NIF/CIF:</strong> {{ $factura->comercioFiscal->nif_cif }}</div>

        @if($factura->comercioFiscal->direccion)
            <div><strong>Dirección:</strong> {{ $factura->comercioFiscal->direccion }}</div>
        @endif

        <div><strong>Localidad:</strong>
            @if($factura->comercioFiscal->codigo_postal){{ $factura->comercioFiscal->codigo_postal }} @endif
            {{ $factura->comercioFiscal->municipio }}
            @if($factura->comercioFiscal->provincia), {{ $factura->comercioFiscal->provincia }}@endif
        </div>

        @if($factura->comercioFiscal->pais && $factura->comercioFiscal->pais !== 'ES')
            <div><strong>País:</strong> {{ $factura->comercioFiscal->pais }}</div>
        @endif

        @if($factura->comercioFiscal->email)
            <div><strong>Email:</strong> {{ $factura->comercioFiscal->email }}</div>
        @endif
    </div>
    @endif

    {{-- Línea separadora --}}
    <div class="line"></div>

    {{-- PRODUCTOS --}}
    @foreach($factura->lineas as $linea)
    <div class="item">
        <div class="item-name">{{ $linea->cantidad }}x {{ $linea->descripcion }}</div>

        @if($linea->adiciones && count($linea->adiciones) > 0)
            @foreach($linea->adiciones as $adicion)
                <div class="addon">+ {{ $adicion['nombre'] ?? '' }}</div>
            @endforeach
        @endif

        <div class="item-details">
            <span>{{ $linea->cantidad }} x €{{ number_format($linea->precio_unitario, 2) }}</span>
            <span class="bold">€{{ number_format($linea->total_linea, 2) }}</span>
        </div>
    </div>
    @endforeach

    {{-- TOTALES --}}
    <div class="totals">
        <div class="total-row">
            <span>Base Imponible:</span>
            <span>€{{ number_format($factura->base_imponible, 2) }}</span>
        </div>
        <div class="total-row">
            <span>IVA ({{ rtrim(rtrim(number_format($factura->lineas->first()->tipo_iva ?? 10, 2), '0'), '.') }}%):</span>
            <span>€{{ number_format($factura->total_iva, 2) }}</span>
        </div>
        <div class="total-row final-total">
            <span>TOTAL:</span>
            <span>€{{ number_format($factura->total_factura, 2) }}</span>
        </div>
    </div>

    {{-- VERIFACTU --}}
    @if($restaurante->fiscal_habilitado && $factura)
    <div class="fiscal-box">
        <div class="section-title">CAMPOS VERIFACTU</div>
        <div class="center small-text" style="margin-bottom: 4px;">
            <em>Factura emitida por sistema VeriFactu</em>
        </div>

        @if($factura->verifactu_id)
            <div><strong>Estado:</strong> ✅ Comunicada a AEAT</div>
            <div><strong>UUID:</strong> {{ Str::limit($factura->verifactu_id, 40, '...') }}</div>

            @if($factura->verifactu_huella)
                <div><strong>Huella Digital:</strong> {{ Str::limit($factura->verifactu_huella, 40, '...') }}</div>
            @endif

            @if($factura->verifactu_timestamp)
                <div><strong>Timestamp:</strong> {{ \Carbon\Carbon::parse($factura->verifactu_timestamp)->format('d/m/Y H:i:s') }}</div>
            @endif

            {{-- QR Code VeriFactu --}}
            @if($factura->verifactu_qr_data)
            <div class="center" style="margin-top: 6px;">
                <div class="small-text" style="margin-bottom: 4px;"><strong>Código QR VeriFactu - AEAT</strong></div>
                <img src="data:image/png;base64,{{ $factura->verifactu_qr_data }}"
                     alt="QR VeriFactu"
                     style="width: 100px; height: 100px; border: 1px solid #ddd; padding: 2px; background: white; display: block; margin: 0 auto;">
                <div class="small-text" style="margin-top: 4px;">Escanee para verificar autenticidad</div>
            </div>
            @endif
        @else
            <div><strong>Estado:</strong> ⏳ Pendiente de comunicar</div>
            <div class="small-text" style="margin-top: 4px;">
                La factura será comunicada a la AEAT próximamente. Puede volver a descargar el ticket en unos momentos para obtener el código QR de verificación.
            </div>
        @endif
    </div>
    @endif

    {{-- PIE DE PÁGINA --}}
    <div class="footer">
        <div>¡Gracias por su visita!</div>
        @if($factura->tipo_factura === 'F1')
            <div style="margin-top: 6px; font-size: 9px;">
                ✓ Factura emitida conforme a VeriFactu (AEAT)
            </div>
        @else
            <div style="margin-top: 6px; font-size: 9px;">
                ✓ Factura simplificada emitida por sistema VeriFactu
            </div>
        @endif
        <div style="margin-top: 4px;">www.flexfood.es</div>
    </div>

    {{-- Script para auto-imprimir --}}
    <script>
        window.onload = function() {
            // Pequeño delay para asegurar que las imágenes (QR, logo) se carguen
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
