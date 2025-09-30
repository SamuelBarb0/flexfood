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
}
