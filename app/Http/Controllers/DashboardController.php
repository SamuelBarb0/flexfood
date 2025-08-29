<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Orden;
use App\Models\Categoria;
use App\Models\Restaurante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Restaurante $restaurante)
    {
        // Mesas SOLO de este restaurante
        $mesas = Mesa::where('restaurante_id', $restaurante->id)
            ->orderBy('nombre')
            ->get();

        $mesasConEstado = $mesas->map(function ($mesa) use ($restaurante) {
            // Orden activa de esta mesa y restaurante (no finalizada)
            $orden = Orden::where('restaurante_id', $restaurante->id)
                ->where('mesa_id', $mesa->id)
                ->where('activo', true)
                ->where('estado', '!=', 4)
                ->latest()
                ->first();

            // Mesa libre (sin orden activa)
            if (!$orden) {
                return [
                    'id'           => $mesa->id,         // <- ID real de la mesa
                    'numero'       => $mesa->nombre,     // número visible
                    'estado'       => 0,
                    'estado_texto' => 'Libre',
                    'color'        => 'gray',
                    'tiempo'       => null,
                    'total'        => 0,
                    'cuenta'       => [],
                    'orden_id'     => null,              // <- sin orden
                ];
            }

            $tiempo = $orden->created_at->diffForHumans(null, true);

            // Normalizar items de la cuenta
            $cuenta = collect($orden->productos)->map(function ($item) {
                $precioBase = (float) ($item['precio_base'] ?? $item['precio'] ?? 0);
                $cantidad   = (int)   ($item['cantidad'] ?? 1);

                $adiciones  = collect($item['adiciones'] ?? [])->map(fn($a) => [
                    'nombre' => $a['nombre'],
                    'precio' => (float) $a['precio'],
                ]);

                $precioAdiciones = (float) $adiciones->sum('precio');
                $subtotal = ($precioBase + $precioAdiciones) * $cantidad;

                return [
                    'nombre'       => $item['nombre'] ?? 'Producto',
                    'precio_base'  => $precioBase,
                    'cantidad'     => $cantidad,
                    'subtotal'     => $subtotal,
                    'adiciones'    => $adiciones->toArray(),
                ];
            });

            $totalCalculado = (float) $cuenta->sum('subtotal');

            // Estado segun orden
            return match ((int) $orden->estado) {
                1 => [
                    'id'           => $mesa->id,         // <- ID real de la mesa
                    'numero'       => $mesa->nombre,
                    'estado'       => 1,
                    'estado_texto' => 'Activa',
                    'color'        => 'green',
                    'tiempo'       => $tiempo,
                    'total'        => $totalCalculado,
                    'cuenta'       => $cuenta->toArray(),
                    'orden_id'     => $orden->id,        // <- ID de la orden activa
                ],
                2 => [
                    'id'           => $mesa->id,
                    'numero'       => $mesa->nombre,
                    'estado'       => 2,
                    'estado_texto' => 'Ocupada',
                    'color'        => 'blue',
                    'tiempo'       => $tiempo,
                    'total'        => $totalCalculado,
                    'cuenta'       => $cuenta->toArray(),
                    'orden_id'     => $orden->id,
                ],
                3 => [
                    'id'           => $mesa->id,
                    'numero'       => $mesa->nombre,
                    'estado'       => 3,
                    'estado_texto' => 'Pide la Cuenta',
                    'color'        => 'orange',
                    'tiempo'       => $tiempo,
                    'total'        => $totalCalculado,
                    'cuenta'       => $cuenta->toArray(),
                    'orden_id'     => $orden->id,
                ],
                default => [
                    'id'           => $mesa->id,
                    'numero'       => $mesa->nombre,
                    'estado'       => 0,
                    'estado_texto' => 'Libre',
                    'color'        => 'gray',
                    'tiempo'       => null,
                    'total'        => 0,
                    'cuenta'       => [],
                    'orden_id'     => $orden->id, // opcional; puedes dejarlo null si prefieres
                ],
            };
        });

        // ✅ Ingresos del restaurante (solo órdenes finalizadas)
        $ingresosTotales = Orden::where('restaurante_id', $restaurante->id)
            ->where('estado', 4)
            ->whereDate('created_at', today()) // hoy según tu timezone de la app
            ->sum('total');


        // Categorías y productos del restaurante
        $categorias = Categoria::where('restaurante_id', $restaurante->id)
            ->with(['productos' => fn($q) => $q->where('restaurante_id', $restaurante->id)])
            ->get();

        return view('dashboard', [
            'mesasConEstado'    => $mesasConEstado,
            'ingresosTotales'   => $ingresosTotales,
            'categorias'        => $categorias,
            'restaurante'       => $restaurante,
            'restauranteNombre' => $restaurante->nombre,
        ]);
    }


    public function analiticas(Restaurante $restaurante)
    {
        $hoy = Carbon::today();

        // 💰 Caja del día (órdenes cerradas hoy) filtradas por restaurante
        $caja = Orden::where('restaurante_id', $restaurante->id)
            ->where('estado', 4)
            ->whereDate('updated_at', $hoy)
            ->with('mesa')
            ->get();

        // 🏆 Ranking de platos (solo órdenes de este restaurante hoy)
        $ordenes = Orden::where('restaurante_id', $restaurante->id)
            ->whereDate('created_at', $hoy)
            ->get();

        $conteo = [];
        foreach ($ordenes as $orden) {
            $productos = is_string($orden->productos)
                ? json_decode($orden->productos)
                : $orden->productos;

            if (is_array($productos) || is_object($productos)) {
                foreach ($productos as $producto) {
                    $nombre   = is_array($producto) ? $producto['nombre'] : $producto->nombre;
                    $cantidad = is_array($producto) ? ($producto['cantidad'] ?? 1) : ($producto->cantidad ?? 1);
                    $conteo[$nombre] = ($conteo[$nombre] ?? 0) + $cantidad;
                }
            }
        }

        $ranking = collect($conteo)
            ->map(fn($cantidad, $nombre) => (object) ['nombre' => $nombre, 'total' => $cantidad])
            ->sortByDesc('total')
            ->values();

        // 📊 Pedidos por hora (solo del restaurante)
        $porHora = array_fill(0, 24, 0);
        foreach ($ordenes as $orden) {
            $hora = Carbon::parse($orden->created_at)->hour;
            $porHora[$hora]++;
        }

        return view('analiticas', [
            'caja'         => $caja,
            'ranking'      => $ranking,
            'datosGrafico' => array_values($porHora),
            'restaurante'  => $restaurante,
        ]);
    }

    // Dashboard genérico que redirige si el user tiene restaurante
    public function indexGlobal()
    {
        $user = auth()->user();
        if ($user && $user->restaurante_id) {
            if ($rest = \App\Models\Restaurante::find($user->restaurante_id)) {
                return redirect()->route('rest.dashboard', $rest);
            }
        }
        // Si no tiene restaurante asignado, muestra un dashboard neutro o un selector
        return view('dashboard'); // o lo que uses de fallback
    }
}
