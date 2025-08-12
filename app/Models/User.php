<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'restaurante_id', // 👈 importante para multi-restaurante
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function restaurante()
    {
        return $this->belongsTo(Restaurante::class);
    }

    // (Opcional) helper para filtrar por restaurante
    public function scopeForRestaurante($query, $restauranteId)
    {
        return $query->where('restaurante_id', $restauranteId);
    }
}
