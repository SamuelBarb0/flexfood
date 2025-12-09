<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Factura;

echo "🔍 VERIFICANDO DATOS DE VERIFACTU\n";
echo str_repeat("=", 80) . "\n\n";

// Obtener las últimas 10 facturas
$facturas = Factura::with('restaurante')
    ->whereNotNull('verifactu_id')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

if ($facturas->isEmpty()) {
    echo "❌ No hay facturas con VeriFactu enviado.\n";
    exit;
}

foreach ($facturas as $factura) {
    echo "📄 Factura: {$factura->numero_factura}\n";
    echo "   Restaurante: {$factura->restaurante->nombre}\n";
    echo "   Fecha: {$factura->created_at->format('d/m/Y H:i:s')}\n";
    echo "   Estado: {$factura->estado}\n";
    echo "\n";

    echo "   📊 DATOS VERIFACTU:\n";
    echo "   ├─ UUID: " . ($factura->verifactu_id ? '✅ ' . substr($factura->verifactu_id, 0, 40) . '...' : '❌ No') . "\n";
    echo "   ├─ Huella: " . ($factura->verifactu_huella ? '✅ ' . substr($factura->verifactu_huella, 0, 40) . '...' : '❌ No') . "\n";
    echo "   ├─ QR Base64: " . ($factura->verifactu_qr_data ? '✅ Sí (' . strlen($factura->verifactu_qr_data) . ' caracteres)' : '❌ No') . "\n";
    echo "   ├─ QR URL: " . ($factura->verifactu_qr_url ? '✅ ' . $factura->verifactu_qr_url : '❌ No') . "\n";
    echo "   └─ Estado AEAT: " . strtoupper($factura->aeat_estado) . "\n";
    echo "\n";
    echo str_repeat("-", 80) . "\n\n";
}

echo "\n📈 RESUMEN:\n";
$conQr = $facturas->where('verifactu_qr_data', '!=', null)->count();
$conHuella = $facturas->where('verifactu_huella', '!=', null)->count();
$total = $facturas->count();

echo "   Total facturas: {$total}\n";
echo "   Con QR: {$conQr} (" . round($conQr/$total*100) . "%)\n";
echo "   Con Huella: {$conHuella} (" . round($conHuella/$total*100) . "%)\n";
echo "   Aceptadas AEAT: " . $facturas->where('aeat_estado', 'aceptada')->count() . "\n";
echo "   Pendientes AEAT: " . $facturas->where('aeat_estado', 'pendiente')->count() . "\n";
echo "\n";
