<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaLinea extends Model
{
    protected $fillable = [
        'factura_id',
        'producto_id',
        'orden',
        'descripcion',
        'descripcion_adicional',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'descuento_porcentaje',
        'descuento_importe',
        'base_imponible',
        'tipo_iva',
        'cuota_iva',
        'tipo_recargo',
        'cuota_recargo',
        'total_linea',
        'adiciones',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'descuento_porcentaje' => 'decimal:2',
        'descuento_importe' => 'decimal:2',
        'base_imponible' => 'decimal:2',
        'tipo_iva' => 'decimal:2',
        'cuota_iva' => 'decimal:2',
        'tipo_recargo' => 'decimal:2',
        'cuota_recargo' => 'decimal:2',
        'total_linea' => 'decimal:2',
        'adiciones' => 'array',
    ];

    /**
     * Relación con Factura
     */
    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    /**
     * Relación con Producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Calcular todos los importes de la línea
     */
    public function calcularImportes(): void
    {
        // Calcular subtotal
        $this->subtotal = round($this->cantidad * $this->precio_unitario, 2);

        // Aplicar descuentos
        if ($this->descuento_porcentaje > 0) {
            $this->descuento_importe = round($this->subtotal * ($this->descuento_porcentaje / 100), 2);
        }

        // Calcular base imponible (subtotal - descuento)
        $this->base_imponible = round($this->subtotal - $this->descuento_importe, 2);

        // Calcular cuota de IVA
        $this->cuota_iva = round($this->base_imponible * ($this->tipo_iva / 100), 2);

        // Calcular cuota de recargo (si aplica)
        if ($this->tipo_recargo > 0) {
            $this->cuota_recargo = round($this->base_imponible * ($this->tipo_recargo / 100), 2);
        }

        // Calcular total de la línea
        $this->total_linea = round(
            $this->base_imponible + $this->cuota_iva + $this->cuota_recargo,
            2
        );
    }

    /**
     * Establecer cantidad y recalcular
     */
    public function setCantidad(float $cantidad): void
    {
        $this->cantidad = $cantidad;
        $this->calcularImportes();
    }

    /**
     * Establecer precio unitario y recalcular
     */
    public function setPrecioUnitario(float $precio): void
    {
        $this->precio_unitario = $precio;
        $this->calcularImportes();
    }

    /**
     * Aplicar descuento porcentual
     */
    public function aplicarDescuentoPorcentaje(float $porcentaje): void
    {
        $this->descuento_porcentaje = $porcentaje;
        $this->calcularImportes();
    }

    /**
     * Aplicar descuento por importe fijo
     */
    public function aplicarDescuentoImporte(float $importe): void
    {
        $this->descuento_porcentaje = 0;
        $this->descuento_importe = $importe;
        $this->calcularImportes();
    }

    /**
     * Establecer tipo de IVA y recalcular
     */
    public function setTipoIva(float $tipoIva): void
    {
        $this->tipo_iva = $tipoIva;
        $this->calcularImportes();
    }

    /**
     * Obtener descripción completa (con adiciones)
     */
    public function getDescripcionCompletaAttribute(): string
    {
        $descripcion = $this->descripcion;

        if ($this->adiciones && count($this->adiciones) > 0) {
            $extras = implode(', ', array_column($this->adiciones, 'nombre'));
            $descripcion .= " ({$extras})";
        }

        if ($this->descripcion_adicional) {
            $descripcion .= "\n" . $this->descripcion_adicional;
        }

        return $descripcion;
    }

    /**
     * Obtener precio con IVA incluido
     */
    public function getPrecioConIvaAttribute(): float
    {
        $precioBase = $this->precio_unitario;
        $precioConIva = $precioBase * (1 + ($this->tipo_iva / 100));

        return round($precioConIva, 2);
    }

    /**
     * Crear línea desde producto de orden
     */
    public static function crearDesdeProductoOrden(array $producto, int $orden = 0): self
    {
        $linea = new self();
        $linea->orden = $orden;
        $linea->producto_id = $producto['id'] ?? null;
        $linea->descripcion = $producto['nombre'] ?? 'Producto';
        $linea->cantidad = $producto['cantidad'] ?? 1;

        // Tipo de IVA según config
        $tipoIva = config('verifactu.iva_restauracion', 10);
        $linea->tipo_iva = $tipoIva;

        // El campo 'precio_base' del pedido YA INCLUYE IVA
        // Necesitamos descontar el IVA para obtener la base imponible
        $precioConIva = $producto['precio_base'] ?? ($producto['precio'] ?? 0);

        // Calcular precio sin IVA: precio_con_iva / (1 + tipo_iva/100)
        $linea->precio_unitario = round($precioConIva / (1 + $tipoIva / 100), 2);

        // Guardar adiciones si existen
        if (isset($producto['adiciones']) && is_array($producto['adiciones'])) {
            $linea->adiciones = $producto['adiciones'];

            // Sumar precio de adiciones al precio unitario (también descontando IVA)
            foreach ($producto['adiciones'] as $adicion) {
                $precioAdicionConIva = $adicion['precio'] ?? 0;
                $precioAdicionSinIva = round($precioAdicionConIva / (1 + $tipoIva / 100), 2);
                $linea->precio_unitario += $precioAdicionSinIva;
            }
        }

        $linea->calcularImportes();

        return $linea;
    }

    /**
     * Scope para ordenar por orden
     */
    public function scopeOrdenadas($query)
    {
        return $query->orderBy('orden');
    }
}
