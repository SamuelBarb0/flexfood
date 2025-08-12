<?php
// app/Models/Restaurante.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Restaurante extends Model
{
    protected $fillable = ['nombre', 'slug'];

    // Usar el slug en las rutas: /r/{restaurante}
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /* Relaciones */
    public function categorias()
    {
        return $this->hasMany(Categoria::class); // FK: restaurante_id
    }

    public function productos()
    {
        return $this->hasMany(Producto::class); // FK: restaurante_id
    }

    public function adiciones()
    {
        return $this->hasMany(Adicion::class); // FK: restaurante_id
    }

    public function siteSetting()
    {
        return $this->hasOne(\App\Models\SiteSetting::class); // FK: restaurante_id
    }

    public function ordenes()
    {
        return $this->hasMany(Orden::class, 'restaurante_id');
    }

    // Si en tu código ya usaste "ordens", puedes dejar un alias temporal:
    public function ordens()
    {
        return $this->ordenes();
    }

    public function users()
    {
        return $this->hasMany(\App\Models\User::class); // si guardas restaurante_id en users
    }

    /* Helpers opcionales */
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

    // Por si quieres un scope rápido
    public function scopeById($q, int $id)
    {
        return $q->where('id', $id);
    }
}
