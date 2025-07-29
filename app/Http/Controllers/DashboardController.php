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
                ->where('estado', '!=', 4) // Excluir finalizadas
                ->latest()
                ->first();

            if (!$orden) {
                return [
                    'numero' => $mesa->nombre,
                    'estado' => 0,
                    'estado_texto' => 'Libre',
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
                1 => [
                    'numero' => $mesa->nombre,
                    'estado' => 1,
                    'estado_texto' => 'Activa',
                    'color' => 'green',
                    'tiempo' => $tiempo,
                    'total' => $totalCalculado,
                    'cuenta' => $cuenta,
                ],
                2 => [
                    'numero' => $mesa->nombre,
                    'estado' => 2,
                    'estado_texto' => 'Ocupada',
                    'color' => 'blue',
                    'tiempo' => $tiempo,
                    'total' => $totalCalculado,
                    'cuenta' => $cuenta,
                ],
                3 => [
                    'numero' => $mesa->nombre,
                    'estado' => 3,
                    'estado_texto' => 'Pide la Cuenta',
                    'color' => 'orange',
                    'tiempo' => $tiempo,
                    'total' => $totalCalculado,
                    'cuenta' => $cuenta,
                ],
                default => [
                    'numero' => $mesa->nombre,
                    'estado' => 0,
                    'estado_texto' => 'Libre',
                    'color' => 'gray',
                    'tiempo' => null,
                    'total' => 0,
                    'cuenta' => [],
                ],
            };
        });

        // ✅ Ahora solo se suman las órdenes en estado 4 (finalizadas)
        $ingresosTotales = Orden::where('estado', 4)->sum('total');

        $categorias = Categoria::with('productos')->get();

        return view('dashboard', [
            'mesasConEstado' => $mesasConEstado,
            'ingresosTotales' => $ingresosTotales,
            'categorias' => $categorias,
        ]);
    }
}
