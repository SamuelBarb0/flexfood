<?php

/**
 * Emitir y enviar factura existente a VeriFacti
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\Factura;
use App\Services\InvoiceService;

echo "📄 EMITIR Y ENVIAR FACTURA A VERIFACTI\n";
echo "======================================\n\n";

// Obtener número de factura desde argumentos o usar la última
$numeroFactura = $argv[1] ?? null;

if ($numeroFactura) {
    $factura = Factura::where('numero_factura', $numeroFactura)->first();

    if (!$factura) {
        echo "❌ No se encontró la factura: {$numeroFactura}\n";
        exit(1);
    }
} else {
    // Obtener última factura en borrador
    $factura = Factura::where('estado', 'borrador')
        ->orderBy('id', 'desc')
        ->first();

    if (!$factura) {
        echo "❌ No hay facturas en borrador\n";
        echo "   Crea una nueva factura primero\n";
        exit(1);
    }
}

echo "✓ Factura: {$factura->numero_factura}\n";
echo "  → Estado: {$factura->estado}\n";
echo "  → Estado AEAT: {$factura->aeat_estado}\n";
echo "  → Total: €" . number_format($factura->total_factura, 2) . "\n";
echo "  → Fecha: {$factura->fecha_emision->format('d/m/Y')}\n";
echo "\n";

// Mostrar líneas
echo "📊 Líneas de factura:\n";
foreach ($factura->lineas as $i => $linea) {
    echo "  " . ($i + 1) . ". {$linea->descripcion}\n";
    echo "     Base: €{$linea->base_imponible} | IVA ({$linea->tipo_iva}%): €{$linea->cuota_iva} | Total: €{$linea->total_linea}\n";
}
echo "\n";

// Instanciar servicio de facturación
$invoiceService = app(InvoiceService::class);

try {
    // Emitir factura si está en borrador
    if ($factura->estado === 'borrador') {
        echo "🔄 Emitiendo factura...\n";
        $invoiceService->emitirFactura($factura);
        $factura->refresh();
        echo "✅ Factura emitida: {$factura->numero_factura}\n";
        echo "   Estado: {$factura->estado}\n\n";
    } else {
        echo "ℹ️  La factura ya está emitida\n\n";
    }

    // Enviar a VeriFacti si no se ha enviado
    if ($factura->aeat_estado === 'pendiente') {
        echo "🔄 Enviando a VeriFacti...\n";

        $resultado = $invoiceService->enviarAVeriFactu($factura);
        $factura->refresh();

        echo "✅ Factura enviada a VeriFacti exitosamente\n\n";

        echo "📊 Resultado:\n";
        echo "  → UUID: {$factura->verifactu_id}\n";
        echo "  → Estado AEAT: {$factura->aeat_estado}\n";

        if ($factura->verifactu_qr_url) {
            echo "  → QR URL: {$factura->verifactu_qr_url}\n";
        }

        if (isset($resultado['data'])) {
            echo "\n📄 Datos completos de VeriFacti:\n";
            echo json_encode($resultado['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }

        echo "\n";
        echo "======================================\n";
        echo "✅ Factura procesada correctamente\n";
        echo "\n";
        echo "📝 Verifica la factura en:\n";
        echo "   https://www.verifacti.com/\n";

    } else {
        echo "ℹ️  La factura ya fue enviada a VeriFacti\n";
        echo "  → UUID: {$factura->verifactu_id}\n";
        echo "  → Estado: {$factura->aeat_estado}\n";

        if ($factura->verifactu_qr_url) {
            echo "  → QR URL: {$factura->verifactu_qr_url}\n";
        }
    }

} catch (\Exception $e) {
    echo "❌ Error al procesar factura\n";
    echo "   Mensaje: {$e->getMessage()}\n";
    echo "   Archivo: {$e->getFile()}:{$e->getLine()}\n";

    // Mostrar detalles del error si está disponible
    $factura->refresh();
    if ($factura->verifactu_error) {
        echo "\n📊 Error de VeriFacti:\n";
        echo "   {$factura->verifactu_error}\n";
    }

    exit(1);
}

echo "\n";
