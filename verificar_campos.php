<?php

/**
 * Verificar si los campos de VeriFacti existen en la tabla restaurantes
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "🔍 VERIFICACIÓN DE CAMPOS EN TABLA RESTAURANTES\n";
echo "================================================\n\n";

// Obtener todas las columnas de la tabla restaurantes
$columns = Schema::getColumnListing('restaurantes');

echo "📊 Total de columnas en la tabla: " . count($columns) . "\n\n";

// Campos que necesitamos para VeriFacti
$camposNecesarios = [
    'verifactu_api_username',
    'verifactu_api_key_encrypted',
    'verifactu_api_token',
    'verifactu_token_expires_at',
];

echo "✅ CAMPOS NECESARIOS PARA VERIFACTI:\n";
echo "-------------------------------------\n";

foreach ($camposNecesarios as $campo) {
    $existe = in_array($campo, $columns);
    $estado = $existe ? '✅ EXISTE' : '❌ NO EXISTE';
    echo "{$estado} - {$campo}\n";
}

echo "\n";

// Verificar migración
echo "📋 VERIFICAR SI LA MIGRACIÓN SE EJECUTÓ:\n";
echo "-----------------------------------------\n";

$migracion = DB::table('migrations')
    ->where('migration', 'LIKE', '%add_verifactu_api_credentials%')
    ->first();

if ($migracion) {
    echo "✅ Migración encontrada: {$migracion->migration}\n";
    echo "   Ejecutada en: {$migracion->batch}\n";
} else {
    echo "❌ Migración NO ejecutada\n";
    echo "   Nombre esperado: 2025_11_24_214805_add_verifactu_api_credentials_to_restaurantes_table\n";
    echo "\n";
    echo "   🔧 SOLUCIÓN: Ejecuta este comando:\n";
    echo "      php artisan migrate\n";
}

echo "\n================================================\n";
echo "✅ Verificación completada\n\n";
