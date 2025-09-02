<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Orden;
use App\Models\Categoria;
use App\Models\Restaurante;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function indexGlobal()
    {
        $user = auth()->user();

        // 1) Preferir restaurante guardado en sesión
        if ($slug = session('restaurante_slug')) {
            $rest = Restaurante::where('slug', $slug)->first();
            if ($rest && $this->userCanAccess($user, $rest)) {
                return redirect()->route('rest.dashboard', $rest);
            }
        }

        // 2) Si el usuario tiene restaurante_id en su perfil, redirigir
        if ($user?->restaurante_id) {
            $rest = Restaurante::find($user->restaurante_id);
            if ($rest && $this->userCanAccess($user, $rest)) {
                return redirect()->route('rest.dashboard', $rest);
            }
        }

        // 3) (Opcional) Solo buscar “mis restaurantes” si EXISTE la columna user_id
        $misRestaurantes = collect();
        if (Schema::hasColumn('restaurantes', 'user_id')) {
            $misRestaurantes = Restaurante::where('user_id', $user->id)->get();
            if ($misRestaurantes->count() === 1 && $this->userCanAccess($user, $misRestaurantes->first())) {
                return redirect()->route('rest.dashboard', $misRestaurantes->first());
            }
        }

        // 4) Vista neutra (sin restaurante)
        return view('dashboard', [
            'restaurante'             => null,
            'restauranteNombre'       => null,
            'mesasConEstado'          => collect(),
            'ingresosTotales'         => 0,
            'categorias'              => collect(),
            'restaurantesDisponibles' => $misRestaurantes, // estará vacío si no hay columna user_id
        ]);
    }


    /**
     * GET /r/{restaurante:slug}/dashboard (por restaurante explícito).
     * Requiere slug y valida pertenencia / permisos.
     */
    public function index(Restaurante $restaurante)
    {
        $this->ensureAccess($restaurante);

        // Mesas del restaurante
        $mesas = Mesa::where('restaurante_id', $restaurante->id)
            ->orderBy('nombre')
            ->get();

        $mesasConEstado = $mesas->map(function ($mesa) use ($restaurante) {
            $orden = Orden::where('restaurante_id', $restaurante->id)
                ->where('mesa_id', $mesa->id)
                ->where('activo', true)
                ->where('estado', '!=', 4)     // 4 = finalizada
                ->latest()
                ->first();

            if (!$orden) {
                return [
                    'id'           => $mesa->id,
                    'numero'       => $mesa->nombre,
                    'estado'       => 0,
                    'estado_texto' => 'Libre',
                    'color'        => 'gray',
                    'tiempo'       => null,
                    'total'        => 0,
                    'cuenta'       => [],
                    'orden_id'     => null,
                ];
            }

            $tiempo = $orden->created_at->diffForHumans(null, true);

            $cuenta = collect($orden->productos)->map(function ($item) {
                $precioBase = (float) ($item['precio_base'] ?? $item['precio'] ?? 0);
                $cantidad   = (int)   ($item['cantidad'] ?? 1);

                $adiciones = collect($item['adiciones'] ?? [])->map(fn($a) => [
                    'nombre' => $a['nombre'] ?? '',
                    'precio' => (float) ($a['precio'] ?? 0),
                ]);

                $subtotal = ($precioBase + (float) $adiciones->sum('precio')) * $cantidad;

                return [
                    'nombre'       => $item['nombre'] ?? 'Producto',
                    'precio_base'  => $precioBase,
                    'cantidad'     => $cantidad,
                    'subtotal'     => $subtotal,
                    'adiciones'    => $adiciones->toArray(),
                ];
            });

            $totalCalculado = (float) $cuenta->sum('subtotal');

            return match ((int) $orden->estado) {
                1 => [
                    'id'           => $mesa->id,
                    'numero'       => $mesa->nombre,
                    'estado'       => 1,
                    'estado_texto' => 'Activa',
                    'color'        => 'green',
                    'tiempo'       => $tiempo,
                    'total'        => $totalCalculado,
                    'cuenta'       => $cuenta->toArray(),
                    'orden_id'     => $orden->id,
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
                    'orden_id'     => null,
                ],
            };
        });

        // Ingresos (órdenes finalizadas hoy)
        $ingresosTotales = Orden::where('restaurante_id', $restaurante->id)
            ->where('estado', 4)
            ->whereDate('created_at', today())
            ->sum('total');

        // Categorías con productos del restaurante
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

    /**
     * Analíticas por restaurante.
     */
    public function analiticas(Restaurante $restaurante)
    {
        $this->ensureAccess($restaurante);

        $hoy = Carbon::today();

        // Caja del día (órdenes cerradas hoy)
        $caja = Orden::where('restaurante_id', $restaurante->id)
            ->where('estado', 4)
            ->whereDate('updated_at', $hoy)
            ->with('mesa')
            ->get();

        // Ranking de platos (órdenes de hoy)
        $ordenes = Orden::where('restaurante_id', $restaurante->id)
            ->whereDate('created_at', $hoy)
            ->get();

        $conteo = [];
        foreach ($ordenes as $orden) {
            $productos = is_string($orden->productos)
                ? json_decode($orden->productos, true)
                : $orden->productos;

            if (is_array($productos)) {
                foreach ($productos as $producto) {
                    $nombre   = $producto['nombre']   ?? 'Producto';
                    $cantidad = (int) ($producto['cantidad'] ?? 1);
                    $conteo[$nombre] = ($conteo[$nombre] ?? 0) + $cantidad;
                }
            }
        }

        $ranking = collect($conteo)
            ->map(fn($cantidad, $nombre) => (object) ['nombre' => $nombre, 'total' => $cantidad])
            ->sortByDesc('total')
            ->values();

        // Pedidos por hora (hoy)
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

    /* ===========================
     * Helpers de autorización
     * =========================== */

    /**
     * Aborta con 403 si el usuario no tiene acceso al restaurante.
     */
    private function ensureAccess(Restaurante $restaurante): void
    {
        $user = auth()->user();

        abort_unless($this->userCanAccess($user, $restaurante), 403);
    }

    /**
     * Reglas de acceso:
     * - Admin global -> acceso.
     * - Owner del restaurante -> acceso.
     * - Usuario con restaurante_id igual -> acceso.
     * - (Opcional) Relación pivot user->restaurantes() -> acceso si existe el vínculo.
     */
    private function userCanAccess($user, Restaurante $restaurante): bool
    {
        if (!$user) return false;

        // Admin global
        if (method_exists($user, 'hasRole') && $user->hasRole('administrador')) {
            return true;
        }

        // Owner (solo si la columna existe en la tabla)
        if (Schema::hasColumn('restaurantes', 'user_id')) {
            if ((int) $restaurante->user_id === (int) $user->id) {
                return true;
            }
        }

        // FK directa en users
        if (property_exists($user, 'restaurante_id') && (int)$user->restaurante_id === (int)$restaurante->id) {
            return true;
        }

        // Many-to-many (si existe la relación/pivot)
        if (method_exists($user, 'restaurantes')) {
            try {
                return $user->restaurantes()->where('restaurantes.id', $restaurante->id)->exists();
            } catch (\Throwable $e) {
                // si no hay pivot, ignoramos
            }
        }

        return false;
    }
}
