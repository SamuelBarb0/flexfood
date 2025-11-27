<?php

/**
 * Debug: Ver estructura de productos en orden
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\Orden;

echo "🔍 DEBUG: Estructura de productos en orden\n";
echo "===========================================\n\n";

// Obtener orden #136
$orden = Orden::find(136);

if (!$orden) {
    echo "❌ No se encontró la orden #136\n";
    exit(1);
}

echo "✓ Orden: #{$orden->id}\n";
echo "  → Estado: {$orden->estado}\n";
echo "  → Total: €{$orden->total}\n\n";

echo "📊 PRODUCTOS (estructura completa):\n";
echo "=====================================\n";
echo json_encode($orden->productos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

echo "📊 CAMPOS DISPONIBLES en cada producto:\n";
echo "=========================================\n";
if (is_array($orden->productos) && count($orden->productos) > 0) {
    $primerProducto = $orden->productos[0];
    echo "Producto ejemplo:\n";
    foreach ($primerProducto as $campo => $valor) {
        $tipo = gettype($valor);
        $valorMostrar = is_array($valor) ? '[array con ' . count($valor) . ' elementos]' : $valor;
        echo "  → {$campo} ({$tipo}): {$valorMostrar}\n";
    }
} else {
    echo "  ⚠️  No hay productos en la orden\n";
}

echo "\n";
