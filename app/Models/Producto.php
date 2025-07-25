<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'categoria_id',
        'imagen',        // ✅ Nuevo campo
        'disponible',    // ✅ Nuevo campo
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function adiciones()
    {
        return $this->belongsToMany(Adicion::class, 'adicion_producto');
    }
}
