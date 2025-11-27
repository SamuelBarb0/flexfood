<?php

/**
 * Test de conexión con VeriFacti API
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\Restaurante;
use App\Services\VeriFactiService;

echo "🔍 TEST DE CONEXIÓN CON VERIFACTI\n";
echo "==================================\n\n";

// Obtener restaurante con credenciales
$restaurante = Restaurante::where('fiscal_habilitado', true)->first();

if (!$restaurante) {
    echo "❌ No hay restaurantes con facturación habilitada\n";
    exit(1);
}

echo "✓ Restaurante: {$restaurante->nombre}\n";
echo "  → NIF: {$restaurante->nif}\n";
echo "  → Usuario VeriFacti: " . ($restaurante->verifactu_api_username ?? 'NO CONFIGURADO') . "\n";
echo "  → Tiene API Key: " . (!empty($restaurante->verifactu_api_key) ? 'SÍ' : 'NO') . "\n";
echo "\n";

// Verificar credenciales
if (!$restaurante->tieneCredencialesVeriFactu()) {
    echo "❌ El restaurante no tiene credenciales configuradas\n";
    echo "   Configúralas en: /r/{$restaurante->slug}/settings → Fiscal\n";
    exit(1);
}

echo "✅ Credenciales configuradas correctamente\n\n";

// Instanciar servicio VeriFacti
echo "🔄 Conectando con VeriFacti...\n";
$veriFactiService = app(VeriFactiService::class);

// Obtener API Key desencriptada
$apiKey = $restaurante->verifactu_api_key;

if (empty($apiKey)) {
    echo "❌ Error: No se pudo desencriptar la API Key\n";
    exit(1);
}

echo "  → API Key (primeros 20 caracteres): " . substr($apiKey, 0, 20) . "...\n";
echo "  → API Key (longitud): " . strlen($apiKey) . " caracteres\n";
echo "\n";

// Configurar API Key
$veriFactiService->setApiKey($apiKey);

// Hacer health check
echo "🔄 Verificando conexión (health check)...\n";
$healthResult = $veriFactiService->healthCheck();

if ($healthResult['success']) {
    echo "✅ CONEXIÓN EXITOSA CON VERIFACTI\n\n";
    echo "📊 Información de la cuenta:\n";
    echo "  → NIF: " . ($healthResult['nif'] ?? 'N/A') . "\n";
    echo "  → Entorno: " . ($healthResult['environment'] ?? 'N/A') . "\n";

    if (isset($healthResult['data'])) {
        echo "\n📄 Datos completos:\n";
        echo json_encode($healthResult['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }

    echo "\n";
    echo "==================================\n";
    echo "✅ Todo listo para facturar\n";
    echo "\n";
    echo "📝 Siguiente paso:\n";
    echo "   Ejecuta: php test_facturacion.php\n";
    echo "   Para crear y enviar una factura de prueba\n";
} else {
    echo "❌ ERROR DE CONEXIÓN\n\n";
    echo "📊 Detalles del error:\n";
    echo "  → Error: " . ($healthResult['error'] ?? 'Desconocido') . "\n";

    if (isset($healthResult['code'])) {
        echo "  → Código HTTP: " . $healthResult['code'] . "\n";
    }

    if (isset($healthResult['body'])) {
        echo "  → Respuesta del servidor:\n";
        echo "    " . substr($healthResult['body'], 0, 500) . "\n";
    }

    echo "\n";
    echo "🔧 POSIBLES SOLUCIONES:\n";
    echo "  1. Verifica que la API Key sea correcta\n";
    echo "  2. Verifica que la cuenta esté activa en VeriFacti\n";
    echo "  3. Verifica que la URL de la API sea correcta: " . config('verifactu.api_url') . "\n";
    echo "  4. Contacta con soporte de VeriFacti si el problema persiste\n";

    exit(1);
}

echo "\n";
