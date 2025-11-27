<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\Restaurante;
use App\Models\Factura;

echo "🧪 TEST ACCESO AL PANEL DE FACTURAS\n";
echo "====================================\n\n";

// Obtener restaurante
$restaurante = Restaurante::where('slug', 'admin-principal')->first();

if (!$restaurante) {
    echo "❌ No se encontró el restaurante 'admin-principal'\n";
    exit(1);
}

echo "✅ Restaurante encontrado: {$restaurante->nombre} (ID: {$restaurante->id})\n\n";

// Simular la lógica del index
echo "📊 Ejecutando lógica del index()...\n";
echo "-----------------------------------\n";

$query = $restaurante->facturas()->with(['serieFacturacion', 'orden']);

$facturas = $query->orderBy('fecha_emision', 'desc')
    ->orderBy('id', 'desc')
    ->paginate(20);

echo "✅ Facturas obtenidas: {$facturas->count()}\n";
echo "   Total en BD: {$facturas->total()}\n\n";

// Estadísticas
$estadisticas = [
    'total' => $restaurante->facturas()->count(),
    'pendientes' => $restaurante->facturas()->where('aeat_estado', 'pendiente')->count(),
    'aceptadas' => $restaurante->facturas()->where('aeat_estado', 'aceptada')->count(),
    'rechazadas' => $restaurante->facturas()->where('aeat_estado', 'rechazada')->count(),
];

echo "📈 Estadísticas:\n";
echo "   Total: {$estadisticas['total']}\n";
echo "   Pendientes: {$estadisticas['pendientes']}\n";
echo "   Aceptadas: {$estadisticas['aceptadas']}\n";
echo "   Rechazadas: {$estadisticas['rechazadas']}\n\n";

// Mostrar facturas
echo "📋 Listado de facturas:\n";
echo "----------------------\n";
foreach ($facturas as $factura) {
    echo "  • {$factura->numero_factura}\n";
    echo "    Fecha: {$factura->fecha_emision->format('d/m/Y')}\n";
    echo "    Estado: {$factura->estado} / AEAT: {$factura->aeat_estado}\n";
    echo "    Total: €{$factura->total_factura}\n";
    if ($factura->serieFacturacion) {
        echo "    Serie: {$factura->serieFacturacion->nombre}\n";
    }
    echo "\n";
}

echo "✅ TODO OK - El panel debería funcionar correctamente\n";
echo "\n📍 URL para acceder: http://localhost/r/admin-principal/facturas\n";
