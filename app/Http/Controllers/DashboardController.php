<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Orden;
use App\Models\Categoria;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $mesas = Mesa::orderBy('nombre')->get();

        $mesasConEstado = $mesas->map(function ($mesa) {
            $orden = Orden::where('mesa_id', $mesa->id)
                ->where('activo', true)
                ->latest()
                ->first();

            if (!$orden) {
                return [
                    'numero' => $mesa->nombre,
                    'estado' => 'Libre',
                    'color' => 'gray',
                    'tiempo' => null,
                    'total' => 0,
                    'cuenta' => [],
                ];
            }

            $tiempo = $orden->created_at->diffForHumans(null, true);

            $cuenta = collect($orden->productos)->map(function ($item) {
                $precioBase = $item['precio_base'] ?? $item['precio'] ?? 0;
                $cantidad = $item['cantidad'] ?? 1;
                $adiciones = collect($item['adiciones'] ?? [])->map(function ($a) {
                    return [
                        'nombre' => $a['nombre'],
                        'precio' => $a['precio'],
                    ];
                });

                $precioAdiciones = $adiciones->sum('precio');
                $subtotal = ($precioBase + $precioAdiciones) * $cantidad;

                return [
                    'nombre' => $item['nombre'] ?? 'Producto',
                    'precio_base' => $precioBase,
                    'cantidad' => $cantidad,
                    'subtotal' => $subtotal,
                    'adiciones' => $adiciones->toArray()
                ];
            });


            $totalCalculado = $cuenta->sum('subtotal');

            return match ($orden->estado) {
                0 => [
                    'numero' => $mesa->nombre,
                    'estado' => 'Ocupada',
                    'color' => 'blue',
                    'tiempo' => $tiempo,
                    'total' => 0,
                    'cuenta' => $cuenta,
                ],
                1 => [
                    'numero' => $mesa->nombre,
                    'estado' => 'Activa',
                    'color' => 'green',
                    'tiempo' => $tiempo,
                    'total' => $totalCalculado,
                    'cuenta' => $cuenta,
                ],
                2 => [
                    'numero' => $mesa->nombre,
                    'estado' => 'Pide la Cuenta',
                    'color' => 'orange',
                    'tiempo' => $tiempo,
                    'total' => $totalCalculado,
                    'cuenta' => $cuenta,
                ],
                default => [
                    'numero' => $mesa->nombre,
                    'estado' => 'Libre',
                    'color' => 'gray',
                    'tiempo' => null,
                    'total' => 0,
                    'cuenta' => [],
                ],
            };
        });

        $ingresosTotales = $mesasConEstado
            ->filter(fn($mesa) => in_array($mesa['estado'], ['Activa', 'Pide la Cuenta']))
            ->sum('total');

        $categorias = Categoria::with('productos')->get();

        return view('dashboard', [
            'mesasConEstado' => $mesasConEstado,
            'ingresosTotales' => $ingresosTotales,
            'categorias' => $categorias,
        ]);
    }
}
