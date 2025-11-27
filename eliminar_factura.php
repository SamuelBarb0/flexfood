<?php

/**
 * Eliminar factura errónea
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\Factura;
use Illuminate\Support\Facades\DB;

echo "🗑️  ELIMINAR FACTURA\n";
echo "====================\n\n";

$numeroFactura = $argv[1] ?? 'FF-000003-2025';

$factura = Factura::where('numero_factura', $numeroFactura)->first();

if (!$factura) {
    echo "❌ No se encontró la factura: {$numeroFactura}\n";
    exit(1);
}

echo "⚠️  ADVERTENCIA: Vas a eliminar la siguiente factura:\n\n";
echo "  → Número: {$factura->numero_factura}\n";
echo "  → Estado: {$factura->estado}\n";
echo "  → Total: €{$factura->total_factura}\n";
echo "  → Fecha: {$factura->fecha_emision->format('d/m/Y')}\n";
echo "  → Líneas: " . $factura->lineas->count() . "\n";

if ($factura->verifactu_uuid) {
    echo "\n⚠️  ATENCIÓN: Esta factura fue enviada a VeriFacti\n";
    echo "  → UUID: {$factura->verifactu_uuid}\n";
    echo "  → Estado AEAT: {$factura->verifactu_estado}\n";
}

echo "\n";

// No preguntar confirmación, solo eliminar
DB::transaction(function () use ($factura) {
    // Eliminar líneas
    $factura->lineas()->delete();

    // Eliminar factura
    $factura->delete();
});

echo "✅ Factura {$numeroFactura} eliminada correctamente\n";
echo "\n";
