<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura {{ $factura->numero_factura }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #153958;
        }

        .logo {
            max-width: 150px;
            max-height: 80px;
        }

        .company-info {
            text-align: right;
        }

        .company-info h1 {
            color: #153958;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .company-info p {
            margin: 2px 0;
            font-size: 11px;
        }

        .invoice-info {
            background: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .invoice-info h2 {
            color: #153958;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .invoice-details {
            display: flex;
            justify-content: space-between;
        }

        .invoice-details > div {
            width: 48%;
        }

        .detail-row {
            display: flex;
            margin-bottom: 5px;
        }

        .detail-label {
            font-weight: bold;
            width: 120px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        thead {
            background: #153958;
            color: white;
        }

        th {
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }

        th.center {
            text-align: center;
        }

        th.right {
            text-align: right;
        }

        td {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
        }

        td.center {
            text-align: center;
        }

        td.right {
            text-align: right;
        }

        tbody tr:hover {
            background: #f9f9f9;
        }

        .product-name {
            font-weight: bold;
        }

        .product-additions {
            font-size: 10px;
            color: #666;
            margin-top: 2px;
        }

        .totals {
            float: right;
            width: 300px;
            margin-top: 20px;
        }

        .totals table {
            margin: 0;
        }

        .totals td {
            border: none;
            padding: 5px 10px;
        }

        .totals .total-row {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #153958;
        }

        .footer {
            clear: both;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }

        .qr-code {
            text-align: center;
            margin: 20px 0;
        }

        .qr-code img {
            max-width: 150px;
            border: 1px solid #ddd;
            padding: 5px;
        }

        .verifacti-info {
            background: #e8f4f8;
            padding: 10px;
            margin: 20px 0;
            border-left: 4px solid #3CB28B;
            font-size: 10px;
        }

        .verifacti-info strong {
            display: block;
            margin-bottom: 5px;
            color: #153958;
        }

        @media print {
            body {
                padding: 0;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Encabezado --}}
        <div class="header">
            <div>
                @if($restaurante->siteSetting && $restaurante->siteSetting->logo_path)
                    <img src="{{ public_path($restaurante->siteSetting->logo_path) }}" alt="Logo" class="logo">
                @endif
            </div>
            <div class="company-info">
                <h1>{{ $restaurante->razon_social ?? $restaurante->nombre }}</h1>
                @if($restaurante->nif)
                    <p><strong>NIF:</strong> {{ $restaurante->nif }}</p>
                @endif
                @if($restaurante->direccion_fiscal)
                    <p>{{ $restaurante->direccion_fiscal }}</p>
                @endif
                @if($restaurante->codigo_postal && $restaurante->municipio)
                    <p>{{ $restaurante->codigo_postal }} {{ $restaurante->municipio }}</p>
                @endif
                @if($restaurante->provincia)
                    <p>{{ $restaurante->provincia }}</p>
                @endif
                @if($restaurante->email_fiscal)
                    <p>{{ $restaurante->email_fiscal }}</p>
                @endif
                @if($restaurante->telefono_fiscal)
                    <p>{{ $restaurante->telefono_fiscal }}</p>
                @endif
            </div>
        </div>

        {{-- Información de la factura --}}
        <div class="invoice-info">
            <h2>
                @if($factura->tipo_factura === 'F1')
                    FACTURA {{ $factura->numero_factura }}
                @else
                    FACTURA SIMPLIFICADA {{ $factura->numero_factura }}
                @endif
            </h2>
            <div class="invoice-details">
                <div>
                    <div class="detail-row">
                        <span class="detail-label">Fecha Emisión:</span>
                        <span>{{ \Carbon\Carbon::parse($factura->fecha_emision)->format('d/m/Y') }}</span>
                    </div>
                    @if($factura->fecha_operacion && $factura->fecha_operacion != $factura->fecha_emision)
                    <div class="detail-row">
                        <span class="detail-label">Fecha Operación:</span>
                        <span>{{ \Carbon\Carbon::parse($factura->fecha_operacion)->format('d/m/Y') }}</span>
                    </div>
                    @endif
                    <div class="detail-row">
                        <span class="detail-label">Serie:</span>
                        <span>{{ $factura->serieFacturacion->codigo_serie ?? 'N/A' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Número Serie:</span>
                        <span>{{ $factura->numero_serie }}</span>
                    </div>
                    @if($factura->orden_id)
                    <div class="detail-row">
                        <span class="detail-label">Pedido:</span>
                        <span>#{{ $factura->orden_id }}</span>
                    </div>
                    @endif
                </div>
                <div>
                    <div class="detail-row">
                        <span class="detail-label">Tipo Documento:</span>
                        <span>
                            @if($factura->tipo_factura === 'F1')
                                Factura Completa
                            @else
                                Factura Simplificada
                            @endif
                        </span>
                    </div>
                    @if($restaurante->regimen_fiscal)
                    <div class="detail-row">
                        <span class="detail-label">Régimen Fiscal:</span>
                        <span>{{ $restaurante->regimen_fiscal }}</span>
                    </div>
                    @endif
                    <div class="detail-row">
                        <span class="detail-label">Estado VeriFactu:</span>
                        <span>{{ $factura->aeat_estado === 'aceptada' ? 'Comunicada' : 'Pendiente' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Datos del Cliente (solo para facturas F1) --}}
        @if($factura->tipo_factura === 'F1' && $factura->comercioFiscal)
        <div class="invoice-info" style="background: #fff3cd; border-left: 4px solid #ffc107;">
            <h2 style="color: #856404;">DATOS DEL CLIENTE / DESTINATARIO</h2>
            <div style="margin-top: 10px;">
                <div class="detail-row">
                    <span class="detail-label">Razón Social:</span>
                    <span>{{ $factura->comercioFiscal->razon_social }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">NIF/CIF:</span>
                    <span>{{ $factura->comercioFiscal->nif_cif }}</span>
                </div>
                @if($factura->comercioFiscal->direccion)
                <div class="detail-row">
                    <span class="detail-label">Dirección:</span>
                    <span>{{ $factura->comercioFiscal->direccion }}</span>
                </div>
                @endif
                <div class="detail-row">
                    <span class="detail-label">Localidad:</span>
                    <span>
                        @if($factura->comercioFiscal->codigo_postal){{ $factura->comercioFiscal->codigo_postal }} @endif
                        {{ $factura->comercioFiscal->municipio }}
                        @if($factura->comercioFiscal->provincia), {{ $factura->comercioFiscal->provincia }}@endif
                    </span>
                </div>
                @if($factura->comercioFiscal->pais && $factura->comercioFiscal->pais !== 'ES')
                <div class="detail-row">
                    <span class="detail-label">País:</span>
                    <span>{{ $factura->comercioFiscal->pais }}</span>
                </div>
                @endif
                @if($factura->comercioFiscal->email)
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span>{{ $factura->comercioFiscal->email }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Líneas de factura --}}
        <table>
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th class="center">Cantidad</th>
                    <th class="right">Precio Unit.</th>
                    <th class="right">Base Imponible</th>
                    <th class="center">IVA</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($factura->lineas as $linea)
                    <tr>
                        <td>
                            <div class="product-name">{{ $linea->descripcion }}</div>
                            @if($linea->adiciones && count($linea->adiciones) > 0)
                                <div class="product-additions">
                                    @foreach($linea->adiciones as $adicion)
                                        + {{ $adicion['nombre'] ?? '' }}
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="center">{{ $linea->cantidad }}</td>
                        <td class="right">€{{ number_format($linea->precio_unitario, 2) }}</td>
                        <td class="right">€{{ number_format($linea->base_imponible, 2) }}</td>
                        <td class="center">{{ rtrim(rtrim(number_format($linea->tipo_iva, 2), '0'), '.') }}%</td>
                        <td class="right">€{{ number_format($linea->total_linea, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totales --}}
        <div class="totals">
            <table>
                <tr>
                    <td>Base Imponible:</td>
                    <td class="right">€{{ number_format($factura->base_imponible, 2) }}</td>
                </tr>
                <tr>
                    <td>IVA ({{ rtrim(rtrim(number_format($factura->lineas->first()->tipo_iva ?? 10, 2), '0'), '.') }}%):</td>
                    <td class="right">€{{ number_format($factura->total_iva, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>TOTAL:</td>
                    <td class="right">€{{ number_format($factura->total_factura, 2) }}</td>
                </tr>
            </table>
        </div>

        {{-- Información VeriFactu --}}
        <div style="clear: both;"></div>
        <div class="verifacti-info">
            <strong>📋 INFORMACIÓN VERIFACTU - AEAT</strong>
            @if($factura->verifactu_id)
                <p><strong>UUID (Identificador Único):</strong> {{ $factura->verifactu_id }}</p>
                <p><strong>Estado:</strong> ✅ Factura enviada a VeriFactu correctamente</p>
            @else
                <p><strong>Estado:</strong> ⚠️ Pendiente de envío a VeriFactu/AEAT</p>
            @endif

            @if($factura->verifactu_huella)
                <p><strong>Hash/Huella Digital:</strong> <span style="font-size: 9px; word-break: break-all;">{{ $factura->verifactu_huella }}</span></p>
            @endif

            @if($factura->verifactu_firma)
                <p><strong>Sello/Firma del Software:</strong> <span style="font-size: 9px;">{{ substr($factura->verifactu_firma, 0, 50) }}...</span></p>
            @endif

            @if($factura->verifactu_timestamp)
                <p><strong>Marca de Tiempo:</strong> {{ \Carbon\Carbon::parse($factura->verifactu_timestamp)->format('d/m/Y H:i:s') }}</p>
            @endif

            @if($factura->aeat_csv)
                <p><strong>CSV AEAT:</strong> {{ $factura->aeat_csv }}</p>
            @endif

            @if($factura->verifactu_id)
                <p><strong>Indicador VeriFactu:</strong> ✅ Comunicada a VeriFactu</p>
            @else
                <p><strong>Indicador VeriFactu:</strong> ⚠️ No enviada</p>
            @endif

            @if($factura->verifactu_url_verificacion)
                <p><strong>URL de Verificación:</strong> <span style="font-size: 9px;">{{ $factura->verifactu_url_verificacion }}</span></p>
            @endif
        </div>

        {{-- QR Code VeriFactu --}}
        @if($factura->verifactu_qr_data)
        <div class="qr-code">
            <p style="margin-bottom: 10px; font-weight: bold;">Código QR VeriFactu - AEAT</p>
            <img src="data:image/png;base64,{{ $factura->verifactu_qr_data }}" alt="QR VeriFactu AEAT">
            <p style="font-size: 9px; margin-top: 5px; color: #666;">
                Escanee este código QR para verificar la autenticidad de esta factura en la AEAT
            </p>
        </div>
        @else
        <div class="qr-code" style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;">
            <p style="margin-bottom: 10px; font-weight: bold; color: #856404;">⏳ Código QR Pendiente</p>
            <p style="font-size: 10px; color: #856404;">
                El código QR de VeriFactu se generará una vez que la factura sea comunicada a la AEAT.
                <br>Puede volver a descargar este PDF en unos momentos para obtener el código QR.
            </p>
        </div>
        @endif

        {{-- Pie de página --}}
        <div class="footer">
            <p style="margin-bottom: 5px;">
                @if($factura->tipo_factura === 'F1')
                    ✓ Factura emitida conforme a VeriFactu (AEAT)
                @else
                    ✓ Factura simplificada emitida por sistema VeriFactu
                @endif
            </p>
            <p style="margin-bottom: 5px;">Este documento es una factura electrónica verificada por la AEAT.</p>
            @if($restaurante->verifactu_software_id)
                <p style="margin-bottom: 5px;"><strong>ID Software VeriFactu:</strong> {{ $restaurante->verifactu_software_id }}</p>
            @endif
            <p>Factura generada el {{ now()->format('d/m/Y H:i') }}</p>
            <p style="margin-top: 10px; font-style: italic;">Gracias por su visita</p>
        </div>
    </div>
</body>
</html>
