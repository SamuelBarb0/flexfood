<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orden extends Model
{
    protected $table = 'ordenes';

    protected $fillable = [
        'restaurante_id', // 👈 nuevo
        'mesa_id',
        'mesa_anterior_id',
        'productos',
        'total',
        'estado',
        'activo',
    ];

    protected $casts = [
        'productos' => 'array',
        'estado' => 'integer',
        'activo' => 'boolean',
    ];

    public function mesa()
    {
        return $this->belongsTo(Mesa::class);
    }

    public function mesaAnterior()
    {
        return $this->belongsTo(Mesa::class, 'mesa_anterior_id');
    }

    public function restaurante()
    {
        return $this->belongsTo(Restaurante::class);
    }

    // 🔹 Si ya estás usando $casts, NO decodifiques de nuevo:
    // public function getProductosAttribute($value) { ... }  ⛔️ eliminar

    /**
     * Calcula el total de productos que ya han sido pagados
     */
    public function getTotalProductosPagados(): float
    {
        $productos = $this->productos ?? [];
        $totalPagado = 0;

        foreach ($productos as $producto) {
            $cantidadPagada = $producto['cantidad_pagada'] ?? 0;
            $precioBase = floatval($producto['precio_base'] ?? $producto['precio'] ?? 0);

            // Sumar precio de adiciones
            $precioAdiciones = 0;
            if (isset($producto['adiciones']) && is_array($producto['adiciones'])) {
                foreach ($producto['adiciones'] as $adicion) {
                    $precioAdiciones += floatval($adicion['precio'] ?? 0);
                }
            }

            $precioTotal = $precioBase + $precioAdiciones;
            $totalPagado += $precioTotal * $cantidadPagada;
        }

        return $totalPagado;
    }

    /**
     * Calcula el total pendiente de pago (total - pagado)
     */
    public function getTotalPendientePago(): float
    {
        return $this->total - $this->getTotalProductosPagados();
    }

    /**
     * Marca productos como pagados según sus índices
     */
    public function marcarProductosComoPagados(array $indices): void
    {
        $productos = $this->productos ?? [];

        foreach ($indices as $index) {
            if (isset($productos[$index])) {
                $cantidadTotal = $productos[$index]['cantidad'] ?? 0;
                $productos[$index]['cantidad_pagada'] = $cantidadTotal;
            }
        }

        $this->productos = $productos;
        $this->save();
    }

    /**
     * Elimina productos del ticket según sus índices
     */
    public function eliminarProductos(array $indices): void
    {
        $productos = $this->productos ?? [];

        // Ordenar índices de mayor a menor para evitar problemas al eliminar
        rsort($indices);

        foreach ($indices as $index) {
            if (isset($productos[$index])) {
                unset($productos[$index]);
            }
        }

        // Reindexar el array
        $productos = array_values($productos);

        // Recalcular total
        $nuevoTotal = 0;
        foreach ($productos as $producto) {
            $precioBase = floatval($producto['precio_base'] ?? $producto['precio'] ?? 0);
            $precioAdiciones = 0;

            if (isset($producto['adiciones']) && is_array($producto['adiciones'])) {
                foreach ($producto['adiciones'] as $adicion) {
                    $precioAdiciones += floatval($adicion['precio'] ?? 0);
                }
            }

            $cantidad = intval($producto['cantidad'] ?? 0);
            $nuevoTotal += ($precioBase + $precioAdiciones) * $cantidad;
        }

        $this->productos = $productos;
        $this->total = $nuevoTotal;
        $this->save();
    }
}
