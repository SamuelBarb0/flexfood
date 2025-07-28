<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Adicion extends Model
{
    use HasFactory;

    protected $table = 'adiciones'; // 👈 Esto corrige el nombre

    protected $fillable = [
        'nombre',
        'precio',
    ];

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'adicion_producto');
    }

    public function categorias()
    {
        return $this->belongsToMany(Categoria::class, 'adicion_categoria');
    }
}