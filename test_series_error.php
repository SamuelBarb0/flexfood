<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\Restaurante;
use App\Models\SerieFacturacion;

echo "🧪 TEST ERROR DE SERIES\n";
echo "======================\n\n";

// Obtener restaurante
$restaurante = Restaurante::where('slug', 'admin-principal')->first();

if (!$restaurante) {
    echo "❌ No se encontró el restaurante\n";
    exit(1);
}

echo "✅ Restaurante: {$restaurante->nombre} (ID: {$restaurante->id})\n\n";

// Verificar que existe el método seriesFacturacion
echo "🔍 Verificando métodos del modelo Restaurante:\n";
echo "  → ¿Existe seriesFacturacion()? ";
echo method_exists($restaurante, 'seriesFacturacion') ? "✅ SÍ\n" : "❌ NO\n";

echo "  → ¿Existe series()? ";
echo method_exists($restaurante, 'series') ? "✅ SÍ\n" : "❌ NO\n";

echo "\n";

// Intentar acceder a las series de diferentes formas
echo "📊 Probando diferentes formas de acceso:\n\n";

try {
    echo "1. Usando seriesFacturacion() como método:\n";
    $series = $restaurante->seriesFacturacion()->get();
    echo "   ✅ Funciona - Encontradas {$series->count()} series\n\n";
} catch (\Exception $e) {
    echo "   ❌ Error: {$e->getMessage()}\n\n";
}

try {
    echo "2. Usando seriesFacturacion como propiedad (lazy loading):\n";
    $series = $restaurante->seriesFacturacion;
    echo "   ✅ Funciona - Encontradas {$series->count()} series\n\n";
} catch (\Exception $e) {
    echo "   ❌ Error: {$e->getMessage()}\n\n";
}

try {
    echo "3. Usando load() y luego accediendo:\n";
    $restaurante->load('seriesFacturacion');
    $series = $restaurante->seriesFacturacion;
    echo "   ✅ Funciona - Encontradas {$series->count()} series\n\n";
} catch (\Exception $e) {
    echo "   ❌ Error: {$e->getMessage()}\n\n";
}

try {
    echo "4. Usando series() (sin Facturacion) - DEBERÍA FALLAR:\n";
    $series = $restaurante->series()->get();
    echo "   ⚠️ Funciona inesperadamente - Encontradas {$series->count()} series\n\n";
} catch (\Exception $e) {
    echo "   ✅ Error esperado: {$e->getMessage()}\n\n";
}

// Verificar serie específica
echo "🔍 Intentando cargar serie ID 1:\n";
$serie = SerieFacturacion::find(1);

if ($serie) {
    echo "  ✅ Serie encontrada: {$serie->codigo_serie}\n";
    echo "     Restaurante ID: {$serie->restaurante_id}\n";
    echo "     ¿Pertenece a admin-principal? " . ($serie->restaurante_id === $restaurante->id ? "✅ SÍ" : "❌ NO") . "\n";
} else {
    echo "  ❌ No existe serie con ID 1\n";
}

echo "\n";
echo "📝 Stack trace de llamadas a Restaurante:\n";
$reflectionClass = new \ReflectionClass($restaurante);
echo "  Clase: " . $reflectionClass->getName() . "\n";
echo "  Métodos que contienen 'serie':\n";
foreach ($reflectionClass->getMethods() as $method) {
    if (stripos($method->getName(), 'serie') !== false) {
        echo "    → {$method->getName()}\n";
    }
}
