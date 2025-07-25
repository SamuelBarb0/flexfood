<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mesa extends Model
{
    protected $fillable = [
        'nombre',
        'codigo_qr',
    ];

    public $incrementing = false;

    protected $casts = [
        'nombre' => 'integer',
    ];
}
