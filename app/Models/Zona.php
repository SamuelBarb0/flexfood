<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zona extends Model
{
    protected $fillable = [
        'restaurante_id',
        'nombre',
        'descripcion',
        'orden',
    ];

    public function restaurante()
    {
        return $this->belongsTo(Restaurante::class);
    }

    public function mesas()
    {
        return $this->hasMany(Mesa::class)->orderBy('nombre');
    }
}
