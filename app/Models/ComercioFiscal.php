<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComercioFiscal extends Model
{
    protected $table = 'comercios_fiscales';

    protected $fillable = [
        'restaurante_id',
        'nif_cif',
        'razon_social',
        'nombre_comercial',
        'email',
        'telefono',
        'direccion',
        'municipio',
        'provincia',
        'codigo_postal',
        'pais',
        'activo',
        'notas',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con Restaurante
     */
    public function restaurante(): BelongsTo
    {
        return $this->belongsTo(Restaurante::class);
    }

    /**
     * Relación con Facturas
     */
    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class);
    }

    /**
     * Verificar si tiene NIF válido
     */
    public function tieneNIF(): bool
    {
        return !empty($this->nif_cif);
    }

    /**
     * Verificar si los datos fiscales están completos
     */
    public function datosCompletos(): bool
    {
        return !empty($this->nif_cif) &&
               !empty($this->razon_social) &&
               !empty($this->direccion) &&
               !empty($this->municipio) &&
               !empty($this->provincia) &&
               !empty($this->codigo_postal);
    }

    /**
     * Obtener nombre completo para mostrar
     */
    public function getNombreCompletoAttribute(): string
    {
        return $this->nombre_comercial ?: $this->razon_social;
    }

    /**
     * Obtener dirección completa
     */
    public function getDireccionCompletaAttribute(): string
    {
        $partes = array_filter([
            $this->direccion,
            $this->codigo_postal,
            $this->municipio,
            $this->provincia,
        ]);

        return implode(', ', $partes);
    }

    /**
     * Scope para comercios activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para buscar por NIF
     */
    public function scopePorNIF($query, string $nif)
    {
        return $query->where('nif_cif', $nif);
    }

    /**
     * Scope para buscar por nombre
     */
    public function scopePorNombre($query, string $nombre)
    {
        return $query->where(function ($q) use ($nombre) {
            $q->where('razon_social', 'like', "%{$nombre}%")
              ->orWhere('nombre_comercial', 'like', "%{$nombre}%");
        });
    }
}
