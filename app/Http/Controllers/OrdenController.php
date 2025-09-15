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
            ->where('estado', 1)->where('activo', true)->latest()->get();

        $ordenesEntregadas = Orden::where('restaurante_id', $restaurante->id)
            ->where('estado', 2)->where('activo', true)->latest()->get();

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
            ->where('estado', 1)->where('activo', true)->latest()->get();

        $ordenesEntregadas = Orden::where('restaurante_id', $restaurante->id)
            ->where('estado', 2)->where('activo', true)->latest()->get();

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
        // 1) Validación de entrada
        $validated = $request->validate([
            'carrito' => ['required', 'array', 'min:1'],
            'carrito.*.id'          => ['required', 'integer'],
            'carrito.*.nombre'      => ['required', 'string'],
            'carrito.*.precio_base' => ['required', 'numeric'],
            'carrito.*.cantidad'    => ['required', 'integer', 'min:1'],
            'carrito.*.adiciones'   => ['sometimes', 'array'],

            // Vía oficial por ID (del QR)
            'mesa_id' => [
                'nullable',
                'integer',
                Rule::exists('mesas', 'id')->where(fn($q) => $q->where('restaurante_id', $restaurante->id)),
            ],
            // Fallback por número de mesa (columna "nombre")
            'mesa_numero' => ['nullable', 'integer', 'min:1'],
        ]);

        $carrito = $validated['carrito'];

        // 2) Resolver mesa: primero por ID, si no, por número ("nombre")
        $mesa = null;

        if (!empty($validated['mesa_id'])) {
            $mesa = \App\Models\Mesa::where('id', $validated['mesa_id'])
                ->where('restaurante_id', $restaurante->id)
                ->first();
        }

        if (!$mesa && !empty($validated['mesa_numero'])) {
            $mesa = \App\Models\Mesa::where('restaurante_id', $restaurante->id)
                ->where('nombre', $validated['mesa_numero'])
                ->first();
        }

        // Si enviaron algún dato de mesa y no se encontró, 422
        if ((!empty($validated['mesa_id']) || !empty($validated['mesa_numero'])) && !$mesa) {
            return response()->json([
                'success' => false,
                'message' => 'La mesa indicada no existe para este restaurante.',
                'errors'  => ['mesa' => ['Invalid mesa for this restaurante.']],
            ], 422);
        }

        $mesaId = $mesa?->id;

        // 3) Calcular total
        $total = collect($carrito)->sum(function ($item) {
            $precioBase = (float) ($item['precio_base'] ?? 0);
            $cantidad   = (int)   ($item['cantidad'] ?? 1);

            $totalAdiciones = 0;
            if (!empty($item['adiciones']) && is_array($item['adiciones'])) {
                $totalAdiciones = collect($item['adiciones'])
                    ->sum(fn($a) => (float) ($a['precio'] ?? 0));
            }
            return ($precioBase + $totalAdiciones) * $cantidad;
        });

        // 4) Crear la orden
        $orden = \App\Models\Orden::create([
            'restaurante_id' => $restaurante->id,
            'mesa_id'        => $mesaId,          // null si es para llevar o sin mesa
            'productos'      => $carrito,         // asumiendo cast json en el modelo
            'total'          => $total,
            'estado'         => 0,                 // Pendiente
            'activo'         => true,
        ]);

        return response()->json([
            'success'  => true,
            'orden_id' => $orden->id,
            'message'  => 'Orden registrada correctamente',
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

        $orden->estado = 2; // Entregado
        $orden->save();

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : redirect()->route('comandas.index', $restaurante)->with('success', 'Orden entregada');
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
        return response()->json(['estado' => $mesa->estado]);
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
