<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mesa extends Model
{
    protected $fillable = [
        'restaurante_id',
        'nombre',
        'codigo_qr',
        'zona_id',
    ];

    // Ya no forcemos IDs manuales
    // public $incrementing = false;

    protected $casts = [
        'nombre' => 'integer',
    ];

    public function restaurante()
    {
        return $this->belongsTo(Restaurante::class);
    }

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }
}
