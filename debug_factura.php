<?php

/**
 * Debug: Verificar cálculo de factura
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\Factura;
use App\Models\Orden;

echo "🔍 DEBUG: Cálculo de Factura\n";
echo "=============================\n\n";

// Obtener la última factura
$factura = Factura::orderBy('id', 'desc')->first();

if (!$factura) {
    echo "❌ No hay facturas\n";
    exit(1);
}

echo "📄 FACTURA: {$factura->numero_factura}\n";
echo "  → ID: {$factura->id}\n";
echo "  → Estado: {$factura->estado}\n";
echo "  → Fecha: {$factura->fecha_emision->format('d/m/Y')}\n\n";

// Mostrar orden asociada
if ($factura->orden_id) {
    $orden = Orden::find($factura->orden_id);
    echo "📦 ORDEN ORIGINAL: #{$orden->id}\n";
    echo "  → Total orden: €" . number_format($orden->total, 2) . "\n";
    echo "  → Productos en orden:\n";

    foreach ($orden->productos as $i => $prod) {
        $precioBase = $prod['precio_base'] ?? 0;
        $cantidad = $prod['cantidad'] ?? 1;
        $subtotal = $precioBase * $cantidad;

        echo "    " . ($i + 1) . ". {$prod['nombre']}\n";
        echo "       Precio base: €{$precioBase}\n";
        echo "       Cantidad: {$cantidad}\n";
        echo "       Subtotal: €" . number_format($subtotal, 2) . "\n";
    }
    echo "\n";
}

// Mostrar líneas de factura
echo "📊 LÍNEAS DE FACTURA:\n";
echo "=====================\n\n";

$totalCalculado = 0;

foreach ($factura->lineas as $i => $linea) {
    echo ($i + 1) . ". {$linea->descripcion}\n";
    echo "   Cantidad: {$linea->cantidad}\n";
    echo "   Precio unitario: €" . number_format($linea->precio_unitario, 2) . "\n";
    echo "   Subtotal: €" . number_format($linea->subtotal, 2) . "\n";
    echo "   Descuento: €" . number_format($linea->descuento_importe, 2) . "\n";
    echo "   Base imponible: €" . number_format($linea->base_imponible, 2) . "\n";
    echo "   IVA ({$linea->tipo_iva}%): €" . number_format($linea->cuota_iva, 2) . "\n";
    echo "   Recargo ({$linea->tipo_recargo}%): €" . number_format($linea->cuota_recargo, 2) . "\n";
    echo "   Total línea: €" . number_format($linea->total_linea, 2) . "\n";
    echo "\n";

    $totalCalculado += $linea->total_linea;
}

echo "📊 TOTALES DE FACTURA:\n";
echo "======================\n";
echo "Base imponible: €" . number_format($factura->base_imponible, 2) . "\n";
echo "Total IVA: €" . number_format($factura->total_iva, 2) . "\n";
echo "Total Recargo: €" . number_format($factura->total_recargo, 2) . "\n";
echo "TOTAL FACTURA: €" . number_format($factura->total_factura, 2) . "\n";
echo "\n";
echo "Total calculado (suma de líneas): €" . number_format($totalCalculado, 2) . "\n";
echo "\n";

// Verificar si hay discrepancia
$discrepancia = abs($factura->total_factura - $totalCalculado);
if ($discrepancia > 0.01) {
    echo "⚠️  DISCREPANCIA DETECTADA: €" . number_format($discrepancia, 2) . "\n";
} else {
    echo "✅ Los totales coinciden correctamente\n";
}

echo "\n";
