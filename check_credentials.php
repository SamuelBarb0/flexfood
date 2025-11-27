<?php

/**
 * Script para verificar credenciales VeriFacti en base de datos
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\Restaurante;
use Illuminate\Support\Facades\DB;

echo "🔍 VERIFICACIÓN DE CREDENCIALES VERIFACTI\n";
echo "==========================================\n\n";

// Obtener restaurante
$restaurante = Restaurante::where('nombre', 'ADMIN PRINCIPAL')->first();

if (!$restaurante) {
    echo "❌ No se encontró el restaurante 'ADMIN PRINCIPAL'\n";
    exit(1);
}

echo "✓ Restaurante encontrado: {$restaurante->nombre} (ID: {$restaurante->id})\n\n";

// Consulta directa a la base de datos
echo "📊 VALORES EN BASE DE DATOS (directo):\n";
echo "---------------------------------------\n";

$row = DB::table('restaurantes')
    ->select('id', 'nombre', 'verifactu_api_username', 'verifactu_api_key_encrypted')
    ->where('id', $restaurante->id)
    ->first();

echo "ID: {$row->id}\n";
echo "Nombre: {$row->nombre}\n";
echo "verifactu_api_username: " . ($row->verifactu_api_username ?? 'NULL') . "\n";
echo "verifactu_api_key_encrypted: " . ($row->verifactu_api_key_encrypted ? 'EXISTS (length: ' . strlen($row->verifactu_api_key_encrypted) . ')' : 'NULL/EMPTY') . "\n";
echo "\n";

// Valores a través del modelo
echo "📊 VALORES A TRAVÉS DEL MODELO:\n";
echo "--------------------------------\n";
echo "verifactu_api_username: " . ($restaurante->verifactu_api_username ?? 'NULL') . "\n";
echo "verifactu_api_key_encrypted: " . ($restaurante->verifactu_api_key_encrypted ? 'EXISTS (length: ' . strlen($restaurante->verifactu_api_key_encrypted) . ')' : 'NULL/EMPTY') . "\n";

// Intentar acceder al accessor
try {
    $apiKey = $restaurante->verifactu_api_key;
    echo "verifactu_api_key (desencriptada): " . ($apiKey ? 'EXISTS (length: ' . strlen($apiKey) . ')' : 'NULL/EMPTY') . "\n";
} catch (\Exception $e) {
    echo "verifactu_api_key (desencriptada): ERROR - " . $e->getMessage() . "\n";
}

echo "\n";

// Verificar método
echo "📊 MÉTODO tieneCredencialesVeriFactu():\n";
echo "----------------------------------------\n";
echo "Resultado: " . ($restaurante->tieneCredencialesVeriFactu() ? 'TRUE ✅' : 'FALSE ❌') . "\n";
echo "\n";

// Verificar condiciones individuales
echo "📊 DESGLOSE DE CONDICIONES:\n";
echo "----------------------------\n";
echo "!empty(\$restaurante->verifactu_api_username): " . (!empty($restaurante->verifactu_api_username) ? 'TRUE ✅' : 'FALSE ❌') . "\n";
echo "!empty(\$restaurante->verifactu_api_key_encrypted): " . (!empty($restaurante->verifactu_api_key_encrypted) ? 'TRUE ✅' : 'FALSE ❌') . "\n";

echo "\n==========================================\n";
echo "✅ Verificación completada\n\n";
