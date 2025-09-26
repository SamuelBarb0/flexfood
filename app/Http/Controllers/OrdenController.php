<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Orden;
use App\Models\Restaurante;
use App\Mail\TicketMailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class OrdenController extends Controller
{
    public function index(Request $request, Restaurante $restaurante)
    {
        $ordenesPendientes = Orden::where('restaurante_id', $restaurante->id)
            ->where('estado', 0)->where('activo', true)->latest()->get();

        $ordenesEnProceso = Orden::where('restaurante_id', $restaurante->id)
            ->where('estado', 1)->where('activo', true)->latest()->get()
            ->filter(function ($orden) {
                // Solo mostrar en "En Proceso" si no tiene entregas parciales
                $productos = $orden->productos ?? [];
                foreach ($productos as $producto) {
                    $cantidadEntregada = $producto['cantidad_entregada'] ?? 0;
                    if ($cantidadEntregada > 0) {
                        return false; // Tiene entregas parciales, no mostrar aquí
                    }
                }
                return true;
            });

        $ordenesEntregadas = Orden::where('restaurante_id', $restaurante->id)
            ->where('estado', 2)->where('activo', true)->latest()->get()
            ->merge(
                Orden::where('restaurante_id', $restaurante->id)
                    ->where('estado', 1)->where('activo', true)->latest()->get()
                    ->filter(function ($orden) {
                        // Solo incluir órdenes estado 1 que tengan entregas parciales
                        $productos = $orden->productos ?? [];
                        foreach ($productos as $producto) {
                            $cantidadEntregada = $producto['cantidad_entregada'] ?? 0;
                            if ($cantidadEntregada > 0) {
                                return true; // Tiene entregas parciales, mostrar en servidas
                            }
                        }
                        return false;
                    })
            );

        return view('comandas.index', compact(
            'ordenesPendientes',
            'ordenesEnProceso',
            'ordenesEntregadas',
            'restaurante'
        ));
    }

    public function panel(Request $request, Restaurante $restaurante)
    {
        $ordenesPendientes = Orden::where('restaurante_id', $restaurante->id)
            ->where('estado', 0)->where('activo', true)->latest()->get();

        $ordenesEnProceso = Orden::where('restaurante_id', $restaurante->id)
            ->where('estado', 1)->where('activo', true)->latest()->get()
            ->filter(function ($orden) {
                // Solo mostrar en "En Proceso" si no tiene entregas parciales
                $productos = $orden->productos ?? [];
                foreach ($productos as $producto) {
                    $cantidadEntregada = $producto['cantidad_entregada'] ?? 0;
                    if ($cantidadEntregada > 0) {
                        return false; // Tiene entregas parciales, no mostrar aquí
                    }
                }
                return true;
            });

        $ordenesEntregadas = Orden::where('restaurante_id', $restaurante->id)
            ->where('estado', 2)->where('activo', true)->latest()->get()
            ->merge(
                Orden::where('restaurante_id', $restaurante->id)
                    ->where('estado', 1)->where('activo', true)->latest()->get()
                    ->filter(function ($orden) {
                        // Solo incluir órdenes estado 1 que tengan entregas parciales
                        $productos = $orden->productos ?? [];
                        foreach ($productos as $producto) {
                            $cantidadEntregada = $producto['cantidad_entregada'] ?? 0;
                            if ($cantidadEntregada > 0) {
                                return true; // Tiene entregas parciales, mostrar en servidas
                            }
                        }
                        return false;
                    })
            );

        // Renderiza la MISMA vista y extrae la sección __grid
        $sections = view('comandas.index', compact(
            'ordenesPendientes',
            'ordenesEnProceso',
            'ordenesEntregadas',
            'restaurante'
        ))->renderSections();

        return response($sections['__grid'] ?? '')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }


    public function store(Restaurante $restaurante, Request $request)
    {
        $validated = $request->validate([
            'carrito' => ['required', 'array', 'min:1'],
            'carrito.*.id'          => ['required', 'integer'],
            'carrito.*.nombre'      => ['required', 'string'],
            'carrito.*.precio_base' => ['required', 'numeric'],
            'carrito.*.cantidad'    => ['required', 'integer', 'min:1'],
            'carrito.*.adiciones'   => ['sometimes', 'array'],
            'mesa_id'    => ['nullable', 'integer', Rule::exists('mesas', 'id')->where(fn($q) => $q->where('restaurante_id', $restaurante->id))],
            'mesa_numero' => ['nullable', 'integer', 'min:1'],
        ]);

        // Resolver mesa (igual que ya tenías)
        $mesa = null;
        if (!empty($validated['mesa_id'])) {
            $mesa = Mesa::where('id', $validated['mesa_id'])
                ->where('restaurante_id', $restaurante->id)->first();
        }
        if (!$mesa && !empty($validated['mesa_numero'])) {
            $mesa = Mesa::where('restaurante_id', $restaurante->id)
                ->where('nombre', $validated['mesa_numero'])->first();
        }
        if ((!empty($validated['mesa_id']) || !empty($validated['mesa_numero'])) && !$mesa) {
            return response()->json([
                'success' => false,
                'message' => 'La mesa indicada no existe para este restaurante.',
                'errors'  => ['mesa' => ['Invalid mesa for this restaurante.']],
            ], 422);
        }

        // Normalizar carrito e iniciar entregas en 0
        $carrito = collect($validated['carrito'])->map(function ($item) {
            $adiciones = collect($item['adiciones'] ?? [])->map(fn($a) => [
                'id'     => $a['id']     ?? null,
                'nombre' => $a['nombre'] ?? '',
                'precio' => (float)($a['precio'] ?? 0),
            ])->values()->all();

            return [
                'id'                  => (int)($item['id'] ?? null),
                'nombre'              => (string)($item['nombre'] ?? 'Ítem'),
                'precio_base'         => (float)($item['precio_base'] ?? $item['precio'] ?? 0),
                'cantidad'            => (int)($item['cantidad'] ?? 1),
                'cantidad_entregada'  => 0,
                'adiciones'           => $adiciones,
            ];
        })->values()->all();

        // Calcular total
        $total = collect($carrito)->sum(function ($it) {
            $ads = collect($it['adiciones'])->sum(fn($a) => (float)($a['precio'] ?? 0));
            return ($it['precio_base'] + $ads) * $it['cantidad'];
        });

        $ordenCreada = null;

        DB::transaction(function () use ($restaurante, $mesa, $carrito, $total, &$ordenCreada) {
            // 1) Si hay mesa, archivar órdenes servidas/cuenta/cerradas
            if ($mesa) {
                // Archivar órdenes ya "servidas" (2), "cuenta" (3) o "cerradas" (4) para que no dominen el panel
                Orden::where('restaurante_id', $restaurante->id)
                    ->where('mesa_id', $mesa->id)
                    ->whereIn('estado', [2, 3, 4])
                    ->where('activo', true)
                    ->update(['activo' => false]);
            }

            // 2) Reutilizar orden abierta (0/1) si existe, si no crear una nueva en 1
            $ordenAbierta = null;
            if ($mesa) {
                $ordenAbierta = Orden::where('restaurante_id', $restaurante->id)
                    ->where('mesa_id', $mesa->id)
                    ->where('activo', true)
                    ->whereIn('estado', [0, 1]) // pendiente o en proceso
                    ->latest()->first();
            }

            if ($ordenAbierta) {
                // Append productos al JSON existente
                $productos = collect($ordenAbierta->productos ?? [])
                    ->merge($carrito)->values()->all();

                $ordenAbierta->productos = $productos;
                $ordenAbierta->total = ($ordenAbierta->total ?? 0) + $total;
                $ordenAbierta->estado = 1; // asegurar "en proceso"
                $ordenAbierta->save();

                $ordenCreada = $ordenAbierta;
            } else {
                // Crear nueva orden en “en proceso”
                $ordenCreada = Orden::create([
                    'restaurante_id' => $restaurante->id,
                    'mesa_id'        => $mesa?->id,
                    'productos'      => $carrito,
                    'total'          => $total,
                    'estado'         => 1,      // en proceso
                    'activo'         => true,
                ]);
            }
        });

        return response()->json([
            'success'       => true,
            'orden_id'      => $ordenCreada->id,
            'estado_final'  => (int)$ordenCreada->estado,
            'auto_activada' => true,
            'message'       => 'Pedido agregado a mesa en preparación',
        ], 201);
    }




    public function show(Restaurante $restaurante, Orden $orden)
    {
        $this->ensureOrdenRestaurante($restaurante, $orden);
        $this->authorizeOrden($restaurante, $orden);

        return view('ordenes.show', compact('orden', 'restaurante'));
    }

    public function activar(Restaurante $restaurante, Request $request, Orden $orden)
    {
        $this->ensureOrdenRestaurante($restaurante, $orden);
        $this->authorizeOrden($restaurante, $orden);

        $orden->estado = 1; // En proceso
        $orden->save();

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : redirect()->route('comandas.index', $restaurante)->with('success', 'Orden activada');
    }

    public function entregar(Restaurante $restaurante, Request $request, Orden $orden)
    {
        $this->ensureOrdenRestaurante($restaurante, $orden);
        $this->authorizeOrden($restaurante, $orden);

        // Si es entrega parcial, delega
        if ($request->boolean('entrega_parcial')) {
            return $this->entregarParcial($restaurante, $request, $orden);
        }

        // ENTREGAR TODO: setea cantidad_entregada = cantidad por ítem y normaliza
        $productos = collect($orden->productos ?? [])->map(function ($p) {
            $cantidad = (int)   ($p['cantidad'] ?? 1);
            $p['cantidad']           = $cantidad;
            $p['cantidad_entregada'] = $cantidad; // 👈 full delivered

            $p['precio_base'] = (float) ($p['precio_base'] ?? $p['precio'] ?? 0);
            $p['adiciones'] = collect($p['adiciones'] ?? [])->map(function ($a) {
                return [
                    'id'     => $a['id']     ?? null,
                    'nombre' => $a['nombre'] ?? '',
                    'precio' => (float) ($a['precio'] ?? 0),
                ];
            })->values()->all();

            return $p;
        })->values()->all();

        $orden->productos = $productos;
        $orden->estado = 2; // Entregado
        $orden->save();

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'message' => 'Orden entregada completamente'])
            : redirect()->route('comandas.index', $restaurante)->with('success', 'Orden entregada');
    }


    private function entregarParcial(Restaurante $restaurante, Request $request, Orden $orden)
    {
        $validated = $request->validate([
            'productos_entregar' => ['required', 'array', 'min:1'],
            'productos_entregar.*.indice' => ['required', 'integer', 'min:0'],
            'productos_entregar.*.cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $productosEntregar = $validated['productos_entregar'];
        $productos = $orden->productos; // Array de productos de la orden

        foreach ($productosEntregar as $item) {
            $indice = $item['indice'];
            $cantidadEntregar = $item['cantidad'];

            if (!isset($productos[$indice])) {
                return response()->json([
                    'success' => false,
                    'message' => "Producto en índice {$indice} no encontrado"
                ], 400);
            }

            $producto = $productos[$indice];
            $cantidadTotal = $producto['cantidad'] ?? 1;
            $cantidadEntregada = $producto['cantidad_entregada'] ?? 0;
            $cantidadPendiente = $cantidadTotal - $cantidadEntregada;

            if ($cantidadEntregar > $cantidadPendiente) {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede entregar {$cantidadEntregar} de {$producto['nombre']}, solo hay {$cantidadPendiente} pendientes"
                ], 400);
            }

            // Actualizar cantidad entregada
            $productos[$indice]['cantidad_entregada'] = $cantidadEntregada + $cantidadEntregar;
        }

        // Verificar si todo está entregado
        $todoEntregado = true;
        foreach ($productos as $producto) {
            $cantidadTotal = $producto['cantidad'] ?? 1;
            $cantidadEntregada = $producto['cantidad_entregada'] ?? 0;
            if ($cantidadEntregada < $cantidadTotal) {
                $todoEntregado = false;
                break;
            }
        }

        // Actualizar la orden
        $orden->productos = $productos;
        if ($todoEntregado) {
            $orden->estado = 2; // Entregado completamente
        }
        $orden->save();

        $mensaje = $todoEntregado
            ? 'Productos entregados. Orden completamente entregada.'
            : 'Productos entregados parcialmente.';

        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'todo_entregado' => $todoEntregado
        ]);
    }

    public function desactivar(Restaurante $restaurante, Request $request, Orden $orden)
    {
        $this->ensureOrdenRestaurante($restaurante, $orden);
        $this->authorizeOrden($restaurante, $orden);

        $orden->activo = false;
        $orden->save();

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : redirect()->route('comandas.index', $restaurante)->with('success', 'Orden archivada');
    }

    public function finalizar(Restaurante $restaurante, Request $request)
    {
        Log::info('Cierre de mesa recibido:', $request->all());

        // Leer SIEMPRE mesa_id (FK real). Si no viene, error 422.
        $mesaId = (int) $request->input('mesa_id');
        if ($mesaId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'mesa_id requerido'
            ], 422);
        }

        // Verificar que la mesa pertenece al restaurante
        $mesa = Mesa::where('restaurante_id', $restaurante->id)
            ->where('id', $mesaId)
            ->first();

        if (!$mesa) {
            Log::warning('Mesa no encontrada o no pertenece al restaurante', [
                'restaurante_id' => $restaurante->id,
                'mesa_id' => $mesaId,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Mesa no encontrada'
            ], 404);
        }

        // Buscar orden activa de esa mesa
        $orden = Orden::where('restaurante_id', $restaurante->id)
            ->where('mesa_id', $mesaId)
            ->where('activo', true)
            ->whereIn('estado', [2, 3]) // Entregada o Cuenta solicitada
            ->latest()
            ->first();

        if (!$orden) {
            $ordenDebug = Orden::where('restaurante_id', $restaurante->id)
                ->where('mesa_id', $mesaId)
                ->latest()
                ->first();

            Log::warning('No se encontró orden activa estado 2 o 3 para la mesa', [
                'mesa_id'       => $mesaId,
                'hay_orden'     => (bool) $ordenDebug,
                'estado_ultima' => $ordenDebug->estado ?? null,
                'activo_ultima' => $ordenDebug->activo ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No hay orden elegible para cierre'
            ], 404);
        }

        Log::info('Orden encontrada para cierre:', ['id' => $orden->id]);

        $orden->estado = 4;   // Finalizada
        $orden->activo = false;
        $orden->save();

        return response()->json(['success' => true]);
    }

    public function indexseguimiento(Restaurante $restaurante, Request $request)
    {
        $mesa_id = $request->mesa_id;

        $orden = Orden::where('restaurante_id', $restaurante->id)
            ->where('mesa_id', $mesa_id)
            ->latest()
            ->first();

        return view('seguimiento', [
            'estado'      => $orden->estado ?? 0,
            'mesa_id'     => $mesa_id,
            'restaurante' => $restaurante,
        ]);
    }

    public function pedirCuenta(Request $request, Restaurante $restaurante)
    {
        $data = $request->validate([
            'mesa_id'  => ['required', 'integer'],
            'orden_id' => ['nullable', 'integer'],
        ]);

        $mesaId  = (int) $data['mesa_id'];
        $ordenId = isset($data['orden_id']) ? (int) $data['orden_id'] : null;

        $afectadas = 0;

        DB::transaction(function () use ($restaurante, $mesaId, $ordenId, &$afectadas) {
            $q = Orden::query()
                ->where('restaurante_id', $restaurante->id)
                ->where('mesa_id', $mesaId)
                ->where('estado', 2); // entregado

            if ($ordenId) {
                $q->where('id', $ordenId);
            }

            $afectadas = $q->update(['estado' => 3]); // por cobrar
        });

        return response()->json([
            'ok'            => true,
            'mesa_id'       => $mesaId,
            'orden_id'      => $ordenId,
            'actualizadas'  => $afectadas,
            'nuevo_estado'  => 3,
            'message'       => $ordenId
                ? 'Cuenta solicitada para la orden indicada.'
                : 'Cuenta solicitada: comandas pasadas a estado 3.',
        ]);
    }

    public function estadoOrden(Restaurante $restaurante, Orden $orden)
    {
        // proteger acceso cruzado
        abort_unless((int) $orden->restaurante_id === (int) $restaurante->id, 404);

        return response()->json([
            'id'         => $orden->id,
            'estado'     => (int) $orden->estado, // 0..4
            'mesa_id'    => (int) ($orden->mesa_id ?? 0),
            'created_at' => optional($orden->created_at)?->toIso8601String(),
            'updated_at' => optional($orden->updated_at)?->toIso8601String(),
        ]);
    }

    public function pedirCuentaPedido(Request $request, Restaurante $restaurante)
    {
        $data = $request->validate([
            'mesa_id'  => ['required', 'integer'],
            'orden_id' => ['required', 'integer'],
        ]);

        $mesaId  = (int) $data['mesa_id'];
        $ordenId = (int) $data['orden_id'];

        $orden = Orden::where('restaurante_id', $restaurante->id)
            ->where('mesa_id', $mesaId)
            ->where('id', $ordenId)
            ->where('estado', 2) // solo si estaba entregado
            ->firstOrFail();

        $orden->estado = 3; // cuenta solicitada
        $orden->save();

        return response()->json([
            'ok'      => true,
            'ordenId' => $ordenId,
            'nuevo_estado' => 3,
            'message' => "Se solicitó la cuenta del pedido #{$ordenId}"
        ]);
    }



    public function estadoActual(Restaurante $restaurante, $mesa_id)
    {
        $mesa = Mesa::where('restaurante_id', $restaurante->id)->findOrFail($mesa_id);

        // Derivar el estado de la mesa basándose en las órdenes activas
        $ordenActiva = Orden::where('restaurante_id', $restaurante->id)
            ->where('mesa_id', $mesa_id)
            ->where('activo', true)
            ->whereIn('estado', [0, 1, 2, 3]) // No incluir estado 4 (finalizada)
            ->latest()
            ->first();

        $estadoMesa = $ordenActiva ? 'Ocupada' : 'Libre';

        return response()->json(['estado' => $estadoMesa]);
    }

    public function nuevas(Restaurante $restaurante): JsonResponse
    {
        $cantidad = Orden::where('restaurante_id', $restaurante->id)
            ->where('estado', 0)
            ->where('activo', true)
            ->count();

        return response()->json(['nuevas' => $cantidad]);
    }

    public function historial(Restaurante $restaurante)
    {
        $ordenes = Orden::with('mesa')
            ->where('restaurante_id', $restaurante->id)
            ->orderByDesc('updated_at')
            ->get();

        $estados = [
            0 => 'Pendiente',
            1 => 'En proceso',
            2 => 'Entregada',
            3 => 'Cuenta solicitada',
            4 => 'Cerrada',
        ];

        return view('historial', compact('ordenes', 'estados', 'restaurante'));
    }

    public function generarTicket(Restaurante $restaurante, $ordenId)
    {
        $orden = Orden::where('restaurante_id', $restaurante->id)->findOrFail($ordenId);

        return response()->json([
            'mesa'      => $orden->mesa_id,
            'fecha'     => $orden->created_at->format('d/m/Y, H:i:s'),
            'productos' => $orden->productos,
            'total'     => $orden->total,
        ]);
    }

    /** ===== Helpers ===== */

    /** Autocompleta restaurante_id para órdenes antiguas y evita errores 500 */
    private function ensureOrdenRestaurante(Restaurante $restaurante, Orden $orden): void
    {
        if (is_null($orden->restaurante_id)) {
            // Si la mesa conoce su restaurante, úsalo; si no, usa el de la URL
            if ($orden->mesa && $orden->mesa->restaurante_id) {
                $orden->restaurante_id = $orden->mesa->restaurante_id;
            } else {
                $orden->restaurante_id = $restaurante->id;
            }
            $orden->save();
        }
    }

    /** Proteger acceso cruzado entre restaurantes */
    private function authorizeOrden(Restaurante $restaurante, Orden $orden): void
    {
        abort_unless((int) $orden->restaurante_id === (int) $restaurante->id, 404);
    }

    public function enviarEmail(Request $request, Orden $orden)
    {
        $validated = $request->validate([
            'email' => 'required|email'
        ]);

        Mail::to($validated['email'])->send(new TicketMailable($orden));

        return response()->json([
            'success' => true,
            'message' => 'Ticket enviado correctamente a ' . $validated['email']
        ]);
    }

    public function datosFrescos(Restaurante $restaurante, $ordenId)
    {
        $orden = Orden::where('restaurante_id', $restaurante->id)
            ->where('id', $ordenId)
            ->firstOrFail();

        $productos = collect($orden->productos ?? [])->map(function ($p) use ($orden) {
            $cantidad  = (int)   ($p['cantidad'] ?? 1);
            $entregada = array_key_exists('cantidad_entregada', $p)
                ? (int) $p['cantidad_entregada']
                : 0;

            // (Opcional pero recomendado): si estado es 2 o 3, mostrar como completo
            if (in_array((int)$orden->estado, [2, 3], true)) {
                $entregada = $cantidad;
            }

            return [
                'id'                  => $p['id']        ?? null,
                'nombre'              => $p['nombre']    ?? 'Ítem',
                'precio_base'         => (float)($p['precio_base'] ?? $p['precio'] ?? 0),
                'precio'              => (float)($p['precio_base'] ?? $p['precio'] ?? 0),
                'cantidad'            => $cantidad,
                'cantidad_entregada'  => $entregada, // 👈 nunca falta
                'adiciones'           => collect($p['adiciones'] ?? [])->map(function ($a) {
                    return [
                        'id'     => $a['id']     ?? null,
                        'nombre' => $a['nombre'] ?? '',
                        'precio' => (float)($a['precio'] ?? 0),
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return response()->json([
            'id'        => (int)$orden->id,
            'estado'    => (int)$orden->estado,
            'productos' => $productos,
            'total'     => (float)($orden->total ?? 0),
        ]);
    }


    public function entregadas(Request $request, Restaurante $restaurante)
    {
        $data = $request->validate([
            'mesa_id' => ['required', 'regex:/^\d+$/'], // solo dígitos
        ]);

        $mesaId = (int) $data['mesa_id'];

        // Leemos desde el JSON "productos"; no hay relaciones "detalles"
        $ordenes = Orden::query()
            ->where('restaurante_id', $restaurante->id)
            ->where('mesa_id', $mesaId)
            ->whereIn('estado', [2, 3]) // 2=entregado, 3=cuenta solicitada
            ->orderByDesc('id')
            ->get();

        $payload = $ordenes->map(function ($o) {
            // Normaliza los items desde JSON "productos"
            $items = collect($o->productos ?? [])->map(function ($d) {
                return [
                    'id'          => $d['id']         ?? null,
                    'nombre'      => $d['nombre']     ?? 'Ítem',
                    'cantidad'    => (int)   ($d['cantidad'] ?? 1),
                    'precio_base' => (float) ($d['precio_base'] ?? $d['precio'] ?? 0),
                    'adiciones'   => collect($d['adiciones'] ?? [])->map(function ($a) {
                        return [
                            'id'     => $a['id']     ?? null,
                            'nombre' => $a['nombre'] ?? '',
                            'precio' => (float)($a['precio'] ?? 0),
                        ];
                    })->values()->all(),
                ];
            })->values()->all();

            // Usa total guardado o calcula
            $total = $o->total ?? collect($items)->reduce(function ($acc, $it) {
                $ads = collect($it['adiciones'] ?? [])->sum(fn($a) => (float) ($a['precio'] ?? 0));
                return $acc + ($it['precio_base'] + $ads) * (int) $it['cantidad'];
            }, 0);

            return [
                'id'         => (int) $o->id,
                'estado'     => (int) $o->estado, // 2 o 3
                'mesa_id'    => (int) $o->mesa_id,
                'total'      => (float) $total,
                'created_at' => optional($o->created_at)?->toIso8601String(),
                // clave que espera el front:
                'items'      => $items,
            ];
        })->values();

        return response()->json(['pedidos' => $payload]);
    }
}
