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
            <h2>FACTURA {{ $factura->numero_factura }}</h2>
            <div class="invoice-details">
                <div>
                    <div class="detail-row">
                        <span class="detail-label">Fecha Emisión:</span>
                        <span>{{ $factura->fecha_emision->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Serie:</span>
                        <span>{{ $factura->serieFacturacion->nombre ?? 'N/A' }}</span>
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
                        <span class="detail-label">Estado:</span>
                        <span>{{ ucfirst($factura->estado) }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Estado AEAT:</span>
                        <span>{{ ucfirst($factura->aeat_estado) }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Tipo Factura:</span>
                        <span>{{ $factura->tipo_factura }}</span>
                    </div>
                </div>
            </div>
        </div>

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
                        <td class="center">{{ $linea->tipo_iva }}%</td>
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
                    <td>IVA ({{ $factura->lineas->first()->tipo_iva ?? 10 }}%):</td>
                    <td class="right">€{{ number_format($factura->cuota_iva, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>TOTAL:</td>
                    <td class="right">€{{ number_format($factura->total_factura, 2) }}</td>
                </tr>
            </table>
        </div>

        {{-- Información VeriFacti --}}
        @if($factura->verifactu_id)
        <div style="clear: both;"></div>
        <div class="verifacti-info">
            <strong>Factura verificada por VeriFacti - AEAT</strong>
            <p><strong>UUID:</strong> {{ $factura->verifactu_id }}</p>
            @if($factura->verifactu_huella)
                <p><strong>Huella Digital:</strong> {{ $factura->verifactu_huella }}</p>
            @endif
            @if($factura->aeat_csv)
                <p><strong>CSV AEAT:</strong> {{ $factura->aeat_csv }}</p>
            @endif
        </div>
        @endif

        {{-- QR Code --}}
        @if($factura->qr_url)
        <div class="qr-code">
            <p style="margin-bottom: 10px; font-weight: bold;">Código QR AEAT</p>
            <img src="{{ $factura->qr_url }}" alt="QR AEAT">
        </div>
        @endif

        {{-- Pie de página --}}
        <div class="footer">
            <p>Este documento es una factura electrónica verificada por la AEAT a través de VeriFacti.</p>
            <p>Factura generada el {{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</body>
</html>
