<?php

// app/Models/Orden.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orden extends Model
{
    protected $table = 'ordenes'; // 👈 Esto corrige el nombre

    protected $fillable = [
        'mesa_id',
        'productos',
        'total',
    ];

    protected $casts = [
        'productos' => 'array',
        'estado' => 'integer',
        'activo' => 'boolean',
    ];

    // app/Models/Orden.php
    public function mesa()
    {
        return $this->belongsTo(Mesa::class);
    }

    public function getProductosAttribute($value)
    {
        return json_decode($value, true);
    }
}
