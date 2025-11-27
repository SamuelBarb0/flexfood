<?php

/**
 * Verificar estructura de la tabla facturas
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "🔍 ESTRUCTURA DE LA TABLA FACTURAS\n";
echo "===================================\n\n";

// Obtener todas las columnas
$columns = Schema::getColumnListing('facturas');

echo "📊 Total de columnas: " . count($columns) . "\n\n";

echo "📋 COLUMNAS:\n";
foreach ($columns as $column) {
    echo "  → {$column}\n";
}

echo "\n";

// Verificar campos específicos de VeriFacti
$camposVeriFacti = [
    'verifactu_uuid',
    'verifactu_estado',
    'verifactu_qr_url',
    'verifactu_qr_base64',
    'verifactu_datos_envio',
    'verifactu_error',
    'verifactu_fecha_envio',
];

echo "📊 CAMPOS DE VERIFACTI:\n";
foreach ($camposVeriFacti as $campo) {
    $existe = in_array($campo, $columns);
    echo ($existe ? "  ✅" : "  ❌") . " {$campo}\n";
}

echo "\n";

// Verificar migraciones ejecutadas relacionadas con facturas
echo "📊 MIGRACIONES DE FACTURAS:\n";
$migraciones = DB::table('migrations')
    ->where('migration', 'LIKE', '%factura%')
    ->orWhere('migration', 'LIKE', '%verifactu%')
    ->get();

foreach ($migraciones as $migracion) {
    echo "  ✅ {$migracion->migration}\n";
}

echo "\n";
