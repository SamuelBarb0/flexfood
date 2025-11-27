<?php

/**
 * Script de prueba para VeriFactu
 *
 * Ejecutar con: php test_verifactu.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Restaurante;
use App\Models\Factura;

echo "🧪 TEST VERIFACTU - FlexFood\n";
echo "==============================\n\n";

// Test 1: Verificar que las tablas tienen los nuevos campos
echo "✓ Test 1: Verificando estructura de base de datos...\n";

try {
    $facturaColumns = DB::select("DESCRIBE facturas");
    $hasQR = false;
    $hasAEAT = false;

    foreach ($facturaColumns as $col) {
        if ($col->Field === 'verifactu_qr_url') $hasQR = true;
        if ($col->Field === 'aeat_estado') $hasAEAT = true;
    }

    if ($hasQR && $hasAEAT) {
        echo "  ✅ Tabla 'facturas' tiene los nuevos campos\n";
    } else {
        echo "  ❌ FALTA: Ejecuta 'php artisan migrate'\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    $restauranteColumns = DB::select("DESCRIBE restaurantes");
    $hasModelo = false;
    $hasCreds = false;

    foreach ($restauranteColumns as $col) {
        if ($col->Field === 'modelo_representacion_firmado') $hasModelo = true;
        if ($col->Field === 'verifactu_api_username') $hasCreds = true;
    }

    if ($hasModelo && $hasCreds) {
        echo "  ✅ Tabla 'restaurantes' tiene los nuevos campos\n";
    } else {
        echo "  ❌ FALTA: Ejecuta 'php artisan migrate'\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Test 2: Probar métodos del modelo
echo "✓ Test 2: Probando métodos del modelo...\n";

// Obtener primer restaurante
$restaurante = Restaurante::first();

if (!$restaurante) {
    echo "  ⚠️  No hay restaurantes en la base de datos\n";
    echo "  Crea uno primero desde la interfaz web\n";
    exit(0);
}

echo "  → Restaurante: {$restaurante->nombre} (ID: {$restaurante->id})\n";

// Probar método tieneCredencialesVeriFactu()
$tieneCredenciales = $restaurante->tieneCredencialesVeriFactu();
echo "  → tieneCredencialesVeriFactu(): " . ($tieneCredenciales ? "✅ SÍ" : "❌ NO") . "\n";

// Probar método tieneModeloRepresentacionFirmado()
$tieneModelo = $restaurante->tieneModeloRepresentacionFirmado();
echo "  → tieneModeloRepresentacionFirmado(): " . ($tieneModelo ? "✅ SÍ" : "❌ NO") . "\n";

echo "\n";

// Test 3: Probar guardado de credenciales
echo "✓ Test 3: Probando guardado de credenciales...\n";

try {
    $restaurante->verifactu_api_username = 'B12345678';
    $restaurante->verifactu_api_key = 'test_api_key_1234567890_ABCDEF'; // Se encriptará automáticamente
    $restaurante->save();

    echo "  ✅ Credenciales guardadas correctamente\n";

    // Verificar que se encriptó
    $restaurante->refresh();
    if (!empty($restaurante->verifactu_api_key_encrypted)) {
        echo "  ✅ API Key encriptada: " . substr($restaurante->verifactu_api_key_encrypted, 0, 50) . "...\n";
    }

    // Verificar que se puede desencriptar
    $apiKeyDesencriptada = $restaurante->verifactu_api_key;
    if ($apiKeyDesencriptada === 'test_api_key_1234567890_ABCDEF') {
        echo "  ✅ API Key se desencripta correctamente\n";
    }

    // Verificar método
    if ($restaurante->tieneCredencialesVeriFactu()) {
        echo "  ✅ tieneCredencialesVeriFactu() devuelve TRUE\n";
    }
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Probar métodos de Factura
echo "✓ Test 4: Probando métodos del modelo Factura...\n";

$factura = Factura::first();

if ($factura) {
    echo "  → Factura: {$factura->numero_factura} (ID: {$factura->id})\n";

    // Probar métodos
    echo "  → tieneQR(): " . ($factura->tieneQR() ? "✅ SÍ" : "❌ NO") . "\n";
    echo "  → aceptadaPorAEAT(): " . ($factura->aceptadaPorAEAT() ? "✅ SÍ" : "❌ NO") . "\n";
    echo "  → rechazadaPorAEAT(): " . ($factura->rechazadaPorAEAT() ? "✅ SÍ" : "❌ NO") . "\n";
    echo "  → pendienteAEAT(): " . ($factura->pendienteAEAT() ? "✅ SÍ" : "❌ NO") . "\n";
    echo "  → Estado AEAT actual: {$factura->aeat_estado}\n";
} else {
    echo "  ⚠️  No hay facturas en la base de datos\n";
}

echo "\n";

// Test 5: Verificar rutas
echo "✓ Test 5: Verificando rutas registradas...\n";

$routes = Route::getRoutes();
$found = [];

foreach ($routes->getRoutes() as $route) {
    $name = $route->getName();
    if (str_contains($name, 'fiscal') || str_contains($name, 'verifactu')) {
        $found[] = $name;
    }
}

$expectedRoutes = [
    'fiscal.update',
    'fiscal.credenciales.update',
    'fiscal.certificado.upload',
    'fiscal.modelo-representacion.upload',
    'fiscal.habilitar',
    'fiscal.deshabilitar',
    'webhooks.verifactu',
    'facturas.verificar-estado'
];

foreach ($expectedRoutes as $expected) {
    if (in_array($expected, $found)) {
        echo "  ✅ Ruta '{$expected}' registrada\n";
    } else {
        echo "  ❌ FALTA ruta '{$expected}'\n";
    }
}

echo "\n";
echo "==============================\n";
echo "✅ Tests completados\n";
echo "\n";
echo "📝 Próximos pasos:\n";
echo "1. Accede a: http://localhost:8000/r/{$restaurante->slug}/settings\n";
echo "2. Ve a la pestaña 'Fiscal'\n";
echo "3. Rellena el formulario de Credenciales VeriFactu API\n";
echo "4. Guarda y verifica que aparece el mensaje de éxito\n";
echo "\n";
