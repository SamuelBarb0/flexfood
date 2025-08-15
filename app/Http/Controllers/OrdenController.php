<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Orden;
use App\Models\Restaurante;
use App\Mail\TicketMailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        $mesaNumero = $request->mesa;

        $orden = Orden::where('restaurante_id', $restaurante->id)
            ->where('mesa_id', $mesaNumero)
            ->where('activo', true)
            ->whereIn('estado', [2, 3]) // Entregada o Cuenta solicitada
            ->latest()
            ->first();

        if ($orden) {
            Log::info('Orden encontrada:', ['id' => $orden->id]);
            $orden->estado = 4; // Finalizada
            $orden->activo = false;
            $orden->save();
            return response()->json(['success' => true]);
        }

        Log::warning('No se encontró orden activa estado 2 o 3 para la mesa:', [$mesaNumero]);
        return response()->json(['success' => false], 404);
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

    public function pedirCuenta(Restaurante $restaurante, Request $request)
    {
        $mesa_id = $request->query('mesa_id');

        $orden = Orden::where('restaurante_id', $restaurante->id)
            ->where('mesa_id', $mesa_id)
            ->orderByDesc('updated_at')
            ->first();

        if ($orden && $orden->estado < 3) {
            $orden->estado = 3; // Cuenta solicitada
            $orden->save();
        }

        $estado = $orden->estado ?? 0;
        return view('cuenta.confirmacion', compact('mesa_id', 'estado', 'restaurante'));
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
}
