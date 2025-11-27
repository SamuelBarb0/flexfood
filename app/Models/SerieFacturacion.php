<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SerieFacturacion extends Model
{
    protected $table = 'series_facturacion';

    protected $fillable = [
        'restaurante_id',
        'codigo_serie',
        'nombre',
        'descripcion',
        'ultimo_numero',
        'numero_inicial',
        'prefijo',
        'sufijo',
        'digitos',
        'tipo',
        'punto_venta',
        'activa',
        'es_principal',
        'ano_fiscal',
    ];

    protected $casts = [
        'ultimo_numero' => 'integer',
        'numero_inicial' => 'integer',
        'digitos' => 'integer',
        'activa' => 'boolean',
        'es_principal' => 'boolean',
        'ano_fiscal' => 'integer',
    ];

    /* =========================
     |  Relaciones
     ==========================*/
    public function restaurante()
    {
        return $this->belongsTo(Restaurante::class);
    }

    /* =========================
     |  Métodos de negocio
     ==========================*/

    /**
     * Obtener el siguiente número de factura
     */
    public function obtenerSiguienteNumero(): string
    {
        $this->increment('ultimo_numero');
        $this->refresh();

        $numero = str_pad($this->ultimo_numero, $this->digitos, '0', STR_PAD_LEFT);

        $partes = array_filter([
            $this->prefijo,
            $numero,
            $this->sufijo,
        ]);

        return implode('-', $partes);
    }

    /**
     * Generar número de factura sin incrementar (preview)
     */
    public function previewSiguienteNumero(): string
    {
        $siguienteNumero = $this->ultimo_numero + 1;
        $numero = str_pad($siguienteNumero, $this->digitos, '0', STR_PAD_LEFT);

        $partes = array_filter([
            $this->prefijo,
            $numero,
            $this->sufijo,
        ]);

        return implode('-', $partes);
    }

    /**
     * Resetear contador (útil para nuevo año fiscal)
     */
    public function resetearContador(): void
    {
        $this->update(['ultimo_numero' => $this->numero_inicial - 1]);
    }

    /**
     * Marcar como serie principal (desmarca otras series principales)
     */
    public function marcarComoPrincipal(): void
    {
        // Desmarcar otras series principales del mismo restaurante
        static::where('restaurante_id', $this->restaurante_id)
              ->where('id', '!=', $this->id)
              ->update(['es_principal' => false]);

        $this->update(['es_principal' => true]);
    }

    /* =========================
     |  Scopes
     ==========================*/

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    public function scopePrincipal($query)
    {
        return $query->where('es_principal', true);
    }

    public function scopeDelRestaurante($query, $restauranteId)
    {
        return $query->where('restaurante_id', $restauranteId);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopePorPuntoVenta($query, $puntoVenta)
    {
        return $query->where('punto_venta', $puntoVenta);
    }

    public function scopeDelAnoFiscal($query, $ano)
    {
        return $query->where('ano_fiscal', $ano);
    }

    /* =========================
     |  Validaciones
     ==========================*/

    protected static function booted(): void
    {
        // Al crear una nueva serie, si es_principal=true, desmarcar otras
        static::creating(function (self $serie) {
            if ($serie->es_principal) {
                static::where('restaurante_id', $serie->restaurante_id)
                      ->update(['es_principal' => false]);
            }
        });

        // Al actualizar, si se marca como principal, desmarcar otras
        static::updating(function (self $serie) {
            if ($serie->isDirty('es_principal') && $serie->es_principal) {
                static::where('restaurante_id', $serie->restaurante_id)
                      ->where('id', '!=', $serie->id)
                      ->update(['es_principal' => false]);
            }
        });
    }
}
