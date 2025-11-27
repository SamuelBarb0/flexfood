<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Restaurante extends Model
{
    // ⚠️ añade 'plan' y 'notification_sound_path' a fillable
    protected $fillable = [
        'nombre',
        'slug',
        'plan',
        'notification_sound_path',
        // Campos fiscales VeriFactu
        'razon_social',
        'nombre_comercial',
        'nif',
        'direccion_fiscal',
        'municipio',
        'provincia',
        'codigo_postal',
        'pais',
        'regimen_iva',
        'epigrafe_iae',
        'email_fiscal',
        'telefono_fiscal',
        'fiscal_habilitado',
        'fiscal_habilitado_at',
        // Credenciales VeriFactu API
        'verifactu_api_username',
        'verifactu_api_key', // Atributo virtual para activar el mutador
        'verifactu_api_key_encrypted',
        'verifactu_api_token',
        'verifactu_token_expires_at',
        // Modelo de Representación
        'modelo_representacion_firmado',
        'modelo_representacion_archivo',
        'modelo_representacion_fecha',
        'modelo_representacion_observaciones',
        // Facturación automática
        'facturacion_automatica',
    ];

    protected $casts = [
        'plan' => 'string',
        'fiscal_habilitado' => 'boolean',
        'fiscal_habilitado_at' => 'datetime',
        'verifactu_token_expires_at' => 'datetime',
        'modelo_representacion_firmado' => 'boolean',
        'modelo_representacion_fecha' => 'datetime',
        'facturacion_automatica' => 'boolean',
    ];

    // Usar el slug en las rutas: /r/{restaurante}
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /* =========================
     |  Relaciones
     ==========================*/
    public function categorias()  { return $this->hasMany(Categoria::class); }
    public function productos()   { return $this->hasMany(Producto::class); }
    public function adicions()   { return $this->hasMany(Adicion::class); }
    public function siteSetting() { return $this->hasOne(SiteSetting::class); }
    public function ordenes()     { return $this->hasMany(Orden::class, 'restaurante_id'); }
    public function mesas() { return $this->hasMany(Mesa::class); }
    public function zonas() { return $this->hasMany(Zona::class); }

    // Alias temporal si tuviste "ordens"
    public function ordens() { return $this->ordenes(); }

    public function users()  { return $this->hasMany(User::class); }

    // Relaciones fiscales VeriFactu
    public function certificadosDigitales() { return $this->hasMany(CertificadoDigital::class); }
    public function seriesFacturacion() { return $this->hasMany(SerieFacturacion::class); }
    public function facturas() { return $this->hasMany(Factura::class); }

    // Obtener certificado digital activo y válido
    public function certificadoActivo()
    {
        return $this->hasOne(CertificadoDigital::class)
                    ->activos()
                    ->validos()
                    ->latest();
    }

    // Obtener serie de facturación principal
    public function seriePrincipal()
    {
        return $this->hasOne(SerieFacturacion::class)
                    ->where('es_principal', true)
                    ->activas();
    }

    /* =========================
     |  Boot: slug único
     ==========================*/
    protected static function booted(): void
    {
        static::creating(function (self $r) {
            if (empty($r->slug)) {
                $base = Str::slug($r->nombre);
                $slug = $base;
                $i = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = "{$base}-{$i}";
                    $i++;
                }
                $r->slug = $slug;
            }
        });
    }

    /* =========================
     |  Plan helpers
     ==========================*/
    public const PLAN_BASIC    = 'basic';
    public const PLAN_ADVANCED = 'advanced';
    public const PLAN_LEGACY   = 'legacy';

    /**
     * Normaliza el plan al asignarlo.
     * Opción A: legacy = NULL (recomendado). Si usas enum con 'legacy', quita el branch de null.
     */
    public function setPlanAttribute($value): void
    {
        $v = is_string($value) ? strtolower(trim($value)) : $value;

        // Trata '' y 'legacy' como null (legacy implícito)
        if ($v === '' || $v === null || $v === self::PLAN_LEGACY) {
            $this->attributes['plan'] = null;
            return;
        }

        if (in_array($v, [self::PLAN_BASIC, self::PLAN_ADVANCED], true)) {
            $this->attributes['plan'] = $v;
            return;
        }

        // Valor inesperado → legacy (null)
        $this->attributes['plan'] = null;
    }

    /** Devuelve la clave efectiva de plan (null => 'legacy') */
    public function planKey(): string
    {
        return $this->plan ?: self::PLAN_LEGACY;
    }

    /**
     * Límites del plan desde config/planes_restaurante.php
     * Fallback seguro si no existe el config.
     * Retorna array con keys: only_photos, max_platos, max_qr, max_perfiles
     */
    public function planLimits(): array
    {
        $key = $this->planKey();
        $cfg = config('planes_restaurante');

        if (is_array($cfg) && isset($cfg[$key])) {
            return $cfg[$key];
        }

        // Fallback por si no cargaste config
        return match ($key) {
            self::PLAN_BASIC    => ['only_photos' => true,  'max_platos' => 50,   'max_qr' => 15,  'max_perfiles' => 3],
            self::PLAN_ADVANCED => ['only_photos' => false, 'max_platos' => null, 'max_qr' => null, 'max_perfiles' => null],
            default             => ['only_photos' => false, 'max_platos' => null, 'max_qr' => null,'max_perfiles' => null],
        };
    }

    /* ===== Accessors “de azúcar” para Blade/Controladores ===== */
    public function getOnlyPhotosAttribute(): bool
    {
        return (bool)($this->planLimits()['only_photos'] ?? false);
    }
    public function getMaxPlatosAttribute()
    {
        return $this->planLimits()['max_platos'] ?? null;
    }
    public function getMaxQrAttribute()
    {
        return $this->planLimits()['max_qr'] ?? null;
    }
    public function getMaxPerfilesAttribute()
    {
        return $this->planLimits()['max_perfiles'] ?? null;
    }

    /* =========================
     |  Scopes útiles
     ==========================*/
    public function scopeById($q, int $id) { return $q->where('id', $id); }

    /** Filtra por plan (acepta 'legacy', 'basic', 'advanced' o null) */
    public function scopePlan($q, $plan)
    {
        // Legacy (null) en opción A
        if ($plan === null || $plan === '' || $plan === self::PLAN_LEGACY) {
            return $q->whereNull('plan');
        }
        return $q->where('plan', $plan);
    }

    /* =========================
     |  Métodos fiscales VeriFactu
     ==========================*/

    /**
     * Verifica si el restaurante está habilitado fiscalmente para VeriFactu
     */
    public function fiscalHabilitado(): bool
    {
        return $this->fiscal_habilitado &&
               !empty($this->nif) &&
               $this->certificadoActivo()->exists() &&
               $this->seriePrincipal()->exists();
    }

    /**
     * Verifica si tiene todos los datos fiscales completos
     */
    public function datosFiscalesCompletos(): bool
    {
        return !empty($this->razon_social) &&
               !empty($this->nif) &&
               !empty($this->direccion_fiscal) &&
               !empty($this->municipio) &&
               !empty($this->provincia) &&
               !empty($this->codigo_postal) &&
               !empty($this->regimen_iva);
    }

    /**
     * Habilitar fiscalmente el restaurante
     */
    public function habilitarFiscal(): void
    {
        if ($this->datosFiscalesCompletos() && $this->certificadoActivo()->exists()) {
            $this->update([
                'fiscal_habilitado' => true,
                'fiscal_habilitado_at' => now(),
            ]);
        }
    }

    /**
     * Deshabilitar fiscalmente el restaurante
     */
    public function deshabilitarFiscal(): void
    {
        $this->update([
            'fiscal_habilitado' => false,
        ]);
    }

    /* =========================
     |  Métodos VeriFactu API
     ==========================*/

    /**
     * Obtener API Key desencriptada
     */
    public function getVeriFactuApiKeyAttribute(): ?string
    {
        return $this->verifactu_api_key_encrypted
            ? \Illuminate\Support\Facades\Crypt::decryptString($this->verifactu_api_key_encrypted)
            : null;
    }

    /**
     * Establecer API Key encriptada
     */
    public function setVeriFactuApiKeyAttribute($value): void
    {
        if ($value) {
            $this->attributes['verifactu_api_key_encrypted'] = \Illuminate\Support\Facades\Crypt::encryptString($value);
        }
    }

    /**
     * Verificar si tiene credenciales de VeriFactu configuradas
     */
    public function tieneCredencialesVeriFactu(): bool
    {
        return !empty($this->verifactu_api_username) && !empty($this->verifactu_api_key_encrypted);
    }

    /**
     * Verificar si el token de VeriFactu está vigente
     */
    public function tokenVeriFactuVigente(): bool
    {
        return !empty($this->verifactu_api_token) &&
               $this->verifactu_token_expires_at &&
               $this->verifactu_token_expires_at->isFuture();
    }

    /**
     * Guardar token de VeriFactu
     */
    public function guardarTokenVeriFactu(string $token, string $expiresAt): void
    {
        $this->update([
            'verifactu_api_token' => $token,
            'verifactu_token_expires_at' => $expiresAt,
        ]);
    }

    /**
     * Limpiar token de VeriFactu
     */
    public function limpiarTokenVeriFactu(): void
    {
        $this->update([
            'verifactu_api_token' => null,
            'verifactu_token_expires_at' => null,
        ]);
    }

    /**
     * Verificar si tiene el Modelo de Representación firmado
     */
    public function tieneModeloRepresentacionFirmado(): bool
    {
        return $this->modelo_representacion_firmado === true;
    }

    /**
     * Marcar el Modelo de Representación como firmado
     */
    public function marcarModeloRepresentacionFirmado(string $archivoPath, ?string $observaciones = null): void
    {
        $this->update([
            'modelo_representacion_firmado' => true,
            'modelo_representacion_archivo' => $archivoPath,
            'modelo_representacion_fecha' => now(),
            'modelo_representacion_observaciones' => $observaciones,
        ]);
    }
}
