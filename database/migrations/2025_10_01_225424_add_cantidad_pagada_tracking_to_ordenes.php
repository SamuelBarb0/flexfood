<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Orden;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrar datos existentes para añadir cantidad_pagada = 0 a cada producto
        Orden::all()->each(function ($orden) {
            $productos = $orden->productos ?? [];

            $productosActualizados = array_map(function ($producto) {
                // Solo añadir si no existe ya
                if (!isset($producto['cantidad_pagada'])) {
                    $producto['cantidad_pagada'] = 0;
                }
                return $producto;
            }, $productos);

            $orden->productos = $productosActualizados;
            $orden->save();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar campo cantidad_pagada de todos los productos
        Orden::all()->each(function ($orden) {
            $productos = $orden->productos ?? [];

            $productosActualizados = array_map(function ($producto) {
                unset($producto['cantidad_pagada']);
                return $producto;
            }, $productos);

            $orden->productos = $productosActualizados;
            $orden->save();
        });
    }
};
