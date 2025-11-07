<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Restaurante extends Model
{
    // ⚠️ añade 'plan' y 'notification_sound_path' a fillable
    protected $fillable = ['nombre', 'slug', 'plan', 'notification_sound_path'];

    protected $casts = [
        'plan' => 'string',
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
}
