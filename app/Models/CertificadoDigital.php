<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class CertificadoDigital extends Model
{
    protected $table = 'certificados_digitales';

    protected $fillable = [
        'restaurante_id',
        'nombre_archivo_original',
        'ruta_archivo',
        'password_encriptado',
        'nif_certificado',
        'titular_certificado',
        'fecha_expedicion',
        'fecha_caducidad',
        'valido',
        'detalles_validacion',
        'activo',
        'ultimo_uso',
    ];

    protected $casts = [
        'fecha_expedicion' => 'date',
        'fecha_caducidad' => 'date',
        'valido' => 'boolean',
        'activo' => 'boolean',
        'detalles_validacion' => 'array',
        'ultimo_uso' => 'datetime',
    ];

    /* =========================
     |  Relaciones
     ==========================*/
    public function restaurante()
    {
        return $this->belongsTo(Restaurante::class);
    }

    /* =========================
     |  Accessors & Mutators
     ==========================*/

    /**
     * Desencriptar la contraseña del certificado
     */
    public function getPasswordAttribute(): ?string
    {
        return $this->password_encriptado ? Crypt::decryptString($this->password_encriptado) : null;
    }

    /**
     * Encriptar la contraseña del certificado al guardar
     */
    public function setPasswordAttribute($value): void
    {
        if ($value) {
            $this->attributes['password_encriptado'] = Crypt::encryptString($value);
        }
    }

    /* =========================
     |  Métodos de negocio
     ==========================*/

    /**
     * Verificar si el certificado está vigente
     */
    public function esVigente(): bool
    {
        return $this->valido &&
               $this->activo &&
               $this->fecha_caducidad->isFuture();
    }

    /**
     * Verificar si el certificado está próximo a caducar (30 días)
     */
    public function proximoACaducar(): bool
    {
        return $this->fecha_caducidad->diffInDays(now()) <= 30 &&
               $this->fecha_caducidad->isFuture();
    }

    /**
     * Marcar el certificado como usado
     */
    public function marcarUso(): void
    {
        $this->update(['ultimo_uso' => now()]);
    }

    /* =========================
     |  Scopes
     ==========================*/

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeValidos($query)
    {
        return $query->where('valido', true)
                     ->where('fecha_caducidad', '>', now());
    }

    public function scopeDelRestaurante($query, $restauranteId)
    {
        return $query->where('restaurante_id', $restauranteId);
    }
}
