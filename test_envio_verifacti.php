<?php

/**
 * Test detallado de envío a VeriFacti
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\Factura;
use App\Services\VeriFactiService;

echo "🧪 TEST ENVÍO A VERIFACTI (DETALLADO)\n";
echo "======================================\n\n";

// Obtener última factura emitida
$factura = Factura::where('estado', 'emitida')
    ->where('aeat_estado', 'pendiente')
    ->orderBy('id', 'desc')
    ->first();

if (!$factura) {
    echo "❌ No hay facturas emitidas pendientes de enviar\n";
    exit(1);
}

echo "✓ Factura: {$factura->numero_factura}\n";
echo "  → Total: €{$factura->total_factura}\n";
echo "  → Base: €{$factura->base_imponible}\n";
echo "  → IVA: €{$factura->cuota_iva}\n";
echo "  → Fecha: {$factura->fecha_emision->format('d-m-Y')}\n";
echo "\n";

// Mostrar líneas
echo "📊 Líneas de factura:\n";
foreach ($factura->lineas as $linea) {
    echo "  → {$linea->descripcion}\n";
    echo "    Cantidad: {$linea->cantidad} × €{$linea->precio_unitario}\n";
    echo "    Base: €{$linea->base_imponible} | IVA ({$linea->tipo_iva}%): €{$linea->cuota_iva}\n";
}
echo "\n";

// Construir datos para VeriFacti manualmente
$serie = $factura->serieFacturacion;
$restaurante = $factura->restaurante;

$lineas = [];
foreach ($factura->lineas as $linea) {
    $lineas[] = [
        'base_imponible' => (float) $linea->base_imponible,
        'tipo_impositivo' => (float) $linea->tipo_iva,
        'cuota_repercutida' => (float) $linea->cuota_iva,
    ];
}

$datosFactura = [
    'serie' => $serie->codigo_serie,
    'numero' => (string) $factura->numero_serie,
    'fecha_expedicion' => $factura->fecha_emision->format('d-m-Y'),
    'tipo_factura' => $factura->tipo_factura,
    'descripcion' => substr($factura->descripcion ?? 'Servicios de restauración', 0, 500),
    'lineas' => $lineas,
    'importe_total' => (float) $factura->total_factura,
];

echo "📄 DATOS A ENVIAR A VERIFACTI:\n";
echo "================================\n";
echo json_encode($datosFactura, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

// Instanciar servicio
$veriFactiService = app(VeriFactiService::class);
$veriFactiService->setApiKey($restaurante->verifactu_api_key);

// Mostrar configuración
echo "🔧 CONFIGURACIÓN:\n";
echo "  → Base URL: " . config('verifactu.api_url') . "\n";
echo "  → Endpoint: /verifactu/create\n";
echo "  → URL completa: " . config('verifactu.api_url') . "/verifactu/create\n";
echo "\n";

echo "🔄 Enviando a VeriFacti...\n\n";

try {
    $resultado = $veriFactiService->crearFactura($datosFactura);

    if ($resultado['success']) {
        echo "✅ FACTURA ENVIADA EXITOSAMENTE\n\n";
        echo "📊 Respuesta de VeriFacti:\n";
        echo "  → UUID: {$resultado['uuid']}\n";
        echo "  → Estado: {$resultado['estado']}\n";

        if (isset($resultado['qr_url'])) {
            echo "  → QR URL: {$resultado['qr_url']}\n";
        }

        if (isset($resultado['huella'])) {
            echo "  → Huella: {$resultado['huella']}\n";
        }

        echo "\n📄 Datos completos:\n";
        echo json_encode($resultado['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Guardar en la factura
        $factura->marcarComoEnviada(
            $resultado['uuid'],
            $resultado['data'] ?? null,
            $resultado['qr_url'],
            $resultado['qr_base64'] ?? null
        );

        echo "\n\n✅ Factura actualizada en la base de datos\n";

    } else {
        echo "❌ ERROR AL ENVIAR FACTURA\n\n";
        echo "📊 Detalles del error:\n";
        echo "  → Error: {$resultado['error']}\n";

        if (isset($resultado['code'])) {
            echo "  → Código HTTP: {$resultado['code']}\n";
        }

        if (isset($resultado['body'])) {
            echo "  → Respuesta completa:\n";
            echo "    {$resultado['body']}\n";
        }

        if (isset($resultado['details'])) {
            echo "\n📄 Detalles adicionales:\n";
            echo json_encode($resultado['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        exit(1);
    }

} catch (\Exception $e) {
    echo "❌ EXCEPCIÓN AL ENVIAR FACTURA\n\n";
    echo "  → Mensaje: {$e->getMessage()}\n";
    echo "  → Archivo: {$e->getFile()}:{$e->getLine()}\n";
    echo "\n  Stack trace:\n";
    echo $e->getTraceAsString();

    exit(1);
}

echo "\n";
