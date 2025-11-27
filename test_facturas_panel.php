<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\Factura;
use App\Models\Restaurante;

echo "🧪 TEST PANEL DE FACTURAS\n";
echo "=========================\n\n";

// Verificar facturas
$totalFacturas = Factura::count();
echo "📊 Total facturas en BD: {$totalFacturas}\n\n";

if ($totalFacturas > 0) {
    echo "📋 Últimas 5 facturas:\n";
    echo "---------------------\n";

    Factura::orderBy('id', 'desc')
        ->take(5)
        ->get()
        ->each(function($factura) {
            echo "  • ID: {$factura->id}\n";
            echo "    Número: {$factura->numero_factura}\n";
            echo "    Restaurante ID: {$factura->restaurante_id}\n";
            echo "    Estado: {$factura->estado}\n";
            echo "    AEAT: {$factura->aeat_estado}\n";
            echo "    Total: €{$factura->total_factura}\n";
            echo "\n";
        });
}

// Verificar restaurantes
echo "\n🏪 Restaurantes con facturas:\n";
echo "------------------------------\n";

Restaurante::has('facturas')
    ->withCount('facturas')
    ->get()
    ->each(function($rest) {
        echo "  • {$rest->nombre} (slug: {$rest->slug})\n";
        echo "    Facturas: {$rest->facturas_count}\n";
        echo "    URL panel: /r/{$rest->slug}/facturas\n";
        echo "\n";
    });
