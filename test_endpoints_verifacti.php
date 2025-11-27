<?php

/**
 * Probar diferentes endpoints de VeriFacti
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\Restaurante;

echo "🔍 TEST DE ENDPOINTS VERIFACTI\n";
echo "================================\n\n";

$restaurante = Restaurante::where('fiscal_habilitado', true)->first();
$apiKey = $restaurante->verifactu_api_key;

echo "✓ API Key configurada: " . substr($apiKey, 0, 20) . "...\n\n";

// URLs base a probar
$baseUrls = [
    'https://api.verifacti.com',
    'https://www.verifacti.com/api',
    'https://verifacti.com/api',
];

// Endpoints a probar
$endpoints = [
    '/health',
    '/verifactu/health',
    '/api/health',
    '/status',
];

echo "📊 PROBANDO ENDPOINTS (GET):\n";
echo "=============================\n\n";

foreach ($baseUrls as $baseUrl) {
    echo "🔹 Base URL: {$baseUrl}\n";

    foreach ($endpoints as $endpoint) {
        $fullUrl = $baseUrl . $endpoint;

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Accept' => 'application/json',
                ])
                ->get($fullUrl);

            $status = $response->status();
            $statusText = match($status) {
                200 => '✅ OK',
                401 => '🔐 Unauthorized',
                403 => '🚫 Forbidden',
                404 => '❌ Not Found',
                405 => '⚠️  Method Not Allowed',
                500 => '💥 Server Error',
                default => "❓ {$status}",
            };

            echo "  {$endpoint} → {$statusText}\n";

            if ($status === 200) {
                echo "    Respuesta: " . substr($response->body(), 0, 100) . "\n";
            }

        } catch (\Exception $e) {
            echo "  {$endpoint} → ❌ Error: " . substr($e->getMessage(), 0, 50) . "\n";
        }
    }

    echo "\n";
}

echo "\n📊 PROBANDO ENDPOINT DE FACTURA (POST):\n";
echo "=========================================\n\n";

$facturaTest = [
    'serie' => 'TEST',
    'numero' => '1',
    'fecha_expedicion' => date('d-m-Y'),
    'tipo_factura' => 'F2',
    'descripcion' => 'Test',
    'lineas' => [
        [
            'base_imponible' => 10.0,
            'tipo_impositivo' => 10.0,
            'cuota_repercutida' => 1.0,
        ]
    ],
    'importe_total' => 11.0,
];

$createEndpoints = [
    '/verifactu/create',
    '/facturas',
    '/api/facturas',
    '/verifactu/facturas',
];

foreach ($baseUrls as $baseUrl) {
    echo "🔹 Base URL: {$baseUrl}\n";

    foreach ($createEndpoints as $endpoint) {
        $fullUrl = $baseUrl . $endpoint;

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post($fullUrl, $facturaTest);

            $status = $response->status();
            $statusText = match($status) {
                200, 201 => '✅ SUCCESS!',
                400 => '⚠️  Bad Request',
                401 => '🔐 Unauthorized',
                403 => '🚫 Forbidden',
                404 => '❌ Not Found',
                405 => '⚠️  Method Not Allowed',
                500 => '💥 Server Error',
                default => "❓ {$status}",
            };

            echo "  POST {$endpoint} → {$statusText}\n";

            if ($status >= 200 && $status < 300) {
                echo "    🎉 ¡ESTE ES EL ENDPOINT CORRECTO!\n";
                echo "    Respuesta: " . $response->body() . "\n";
            } elseif ($status === 400) {
                echo "    Error: " . substr($response->body(), 0, 200) . "\n";
            }

        } catch (\Exception $e) {
            echo "  POST {$endpoint} → ❌ Error: " . substr($e->getMessage(), 0, 50) . "\n";
        }
    }

    echo "\n";
}

echo "================================\n";
echo "✅ Test completado\n\n";
