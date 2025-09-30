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
        'mesa_grupo_id',
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

    // Relaciones para fusión de mesas
    public function mesasSecundarias()
    {
        return $this->hasMany(Mesa::class, 'mesa_grupo_id');
    }

    public function mesaGrupo()
    {
        return $this->belongsTo(Mesa::class, 'mesa_grupo_id');
    }

    // Helpers para fusión de mesas
    public function estaFusionada(): bool
    {
        return !is_null($this->mesa_grupo_id);
    }

    public function getMesaPrincipal(): Mesa
    {
        return $this->mesa_grupo_id ? Mesa::find($this->mesa_grupo_id) : $this;
    }

    public function getMesasDelGrupo()
    {
        if ($this->estaFusionada()) {
            $principal = $this->getMesaPrincipal();
            return Mesa::where('mesa_grupo_id', $principal->id)
                       ->orWhere('id', $principal->id)
                       ->get();
        }
        return collect([$this]);
    }
}
