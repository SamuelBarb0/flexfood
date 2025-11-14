<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'restaurante_id',
        'site_name',
        'logo_path',
        'favicon_path',
        'ticket_config',
    ];

    protected $casts = [
        'ticket_config' => 'array',
    ];

    public function restaurante()
    {
        return $this->belongsTo(\App\Models\Restaurante::class);
    }
}
