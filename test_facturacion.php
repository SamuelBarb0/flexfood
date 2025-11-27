<?php

/**
 * Script de prueba para facturación automática
 *
 * Ejecutar con: php test_facturacion.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Restaurante;
use App\Models\Orden;
use App\Services\InvoiceService;

echo "🧪 TEST FACTURACIÓN AUTOMÁTICA - FlexFood\n";
echo "==========================================\n\n";

// Obtener primer restaurante con facturación habilitada
$restaurante = Restaurante::where('fiscal_habilitado', true)->first();

if (!$restaurante) {
    echo "❌ No hay restaurantes con facturación habilitada\n";
    echo "   Habilita la facturación en: /r/{slug}/settings\n";
    exit(1);
}

echo "✓ Restaurante: {$restaurante->nombre}\n";
echo "  → Facturación habilitada: " . ($restaurante->fiscal_habilitado ? 'SÍ' : 'NO') . "\n";
echo "  → Facturación automática: " . ($restaurante->facturacion_automatica ? 'SÍ' : 'NO') . "\n";
echo "  → Tiene credenciales VeriFacti: " . ($restaurante->tieneCredencialesVeriFactu() ? 'SÍ' : 'NO') . "\n\n";

// Obtener última orden finalizada (estado 4)
$orden = Orden::where('restaurante_id', $restaurante->id)
    ->where('estado', 4)
    ->latest()
    ->first();

if (!$orden) {
    echo "⚠️  No hay órdenes finalizadas para probar\n";
    echo "   Finaliza un pedido desde el TPV primero\n";
    exit(0);
}

echo "✓ Orden encontrada: #{$orden->id}\n";
echo "  → Mesa: {$orden->mesa->numero_mesa}\n";
echo "  → Estado: {$orden->estado} (Finalizada)\n";
echo "  → Total: €{$orden->total}\n";
echo "  → Productos: " . count($orden->productos ?? []) . "\n\n";

// Verificar si ya tiene factura
$facturaExistente = App\Models\Factura::where('orden_id', $orden->id)->first();

if ($facturaExistente) {
    echo "ℹ️  Esta orden ya tiene factura:\n";
    echo "  → Número: {$facturaExistente->numero_factura}\n";
    echo "  → Estado: {$facturaExistente->estado}\n";
    echo "  → AEAT: {$facturaExistente->aeat_estado}\n";
    if ($facturaExistente->verifactu_qr_url) {
        echo "  → QR: {$facturaExistente->verifactu_qr_url}\n";
    }
    echo "\n";
    exit(0);
}

// Intentar generar factura
echo "🔄 Generando factura...\n";

try {
    $invoiceService = app(InvoiceService::class);

    // Generar factura
    $factura = $invoiceService->generarFacturaDesdeOrden($orden, [
        'tipo_factura' => 'F2',
    ]);

    echo "  ✅ Factura generada: {$factura->numero_factura}\n";

    // Emitir factura
    $invoiceService->emitirFactura($factura);
    echo "  ✅ Factura emitida\n";

    // Enviar a VeriFacti
    echo "  🌐 Enviando a VeriFacti...\n";
    $resultado = $invoiceService->enviarAVeriFactu($factura);

    if ($resultado['success']) {
        echo "  ✅ Factura enviada a VeriFacti\n";
        echo "     → UUID: {$resultado['uuid']}\n";
        if (isset($resultado['qr_url'])) {
            echo "     → QR URL: {$resultado['qr_url']}\n";
        }
        echo "     → Estado AEAT: {$factura->fresh()->aeat_estado}\n";
    } else {
        echo "  ❌ Error al enviar: {$resultado['error']}\n";
    }

} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
    echo "\n  Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n==========================================\n";
echo "✅ Test completado\n\n";
