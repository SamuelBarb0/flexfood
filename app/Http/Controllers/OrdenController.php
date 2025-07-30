<?php

namespace App\Http\Controllers;

use App\Models\Orden;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class OrdenController extends Controller
{
    public function index()
    {
        $ordenesPendientes = Orden::where('estado', 0)->where('activo', true)->latest()->get();  // Pendientes
        $ordenesEnProceso  = Orden::where('estado', 1)->where('activo', true)->latest()->get();  // En proceso
        $ordenesEntregadas = Orden::where('estado', 2)->where('activo', true)->latest()->get();  // Entregados

        return view('comandas.index', compact('ordenesPendientes', 'ordenesEnProceso', 'ordenesEntregadas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'carrito' => 'required|array|min:1',
            'mesa_id' => 'nullable|integer',
        ]);

        $carrito = $validated['carrito'];
        $mesaId = $validated['mesa_id'] ?? null;

        $total = collect($carrito)->sum(function ($item) {
            $precioBase = floatval($item['precio_base'] ?? 0);
            $cantidad = intval($item['cantidad'] ?? 1);

            $totalAdiciones = 0;
            if (!empty($item['adiciones']) && is_array($item['adiciones'])) {
                $totalAdiciones = collect($item['adiciones'])->sum(function ($a) {
                    return floatval($a['precio'] ?? 0);
                });
            }

            return ($precioBase + $totalAdiciones) * $cantidad;
        });

        $orden = Orden::create([
            'mesa_id'   => $mesaId,
            'productos' => $carrito,
            'total'     => $total,
            'estado'    => 0, // Pendiente
            'activo'    => true,
        ]);

        return response()->json([
            'success'   => true,
            'orden_id'  => $orden->id,
            'message'   => 'Orden registrada correctamente'
        ]);
    }

    public function show(Orden $orden)
    {
        return view('ordenes.show', compact('orden'));
    }

    public function activar(Request $request, Orden $orden)
    {
        $orden->estado = 1; // Estado 1: En proceso
        $orden->save();

        // Si la petición espera JSON (AJAX)
        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        // Si fue una petición normal
        return redirect()->route('comandas.index')->with('success', 'Orden activada');
    }

    public function entregar(Orden $orden)
    {
        $orden->estado = 2; // Estado 2: Entregado
        $orden->save();

        return redirect()->route('comandas.index')->with('success', 'Orden entregada');
    }

    public function desactivar(Orden $orden)
    {
        $orden->activo = false;
        $orden->save();

        return redirect()->route('comandas.index')->with('success', 'Orden archivada');
    }

    public function finalizar(Request $request)
    {
        Log::info('Cierre de mesa recibido:', $request->all());

        $mesaNumero = $request->mesa;

        $orden = Orden::where('mesa_id', $mesaNumero)
            ->where('activo', true)
            ->whereIn('estado', [2, 3]) // Acepta Ocupada o Pide la Cuenta
            ->latest()
            ->first();

        if ($orden) {
            Log::info('Orden encontrada:', ['id' => $orden->id]);

            $orden->estado = 4; // Estado finalizada
            $orden->activo = false;
            $orden->save();

            return response()->json(['success' => true]);
        }

        Log::warning('No se encontró orden activa en estado 2 o 3 para la mesa:', [$mesaNumero]);

        return response()->json(['success' => false], 404);
    }

    public function indexseguimiento(Request $request)
    {
        $mesa_id = $request->mesa_id;

        $orden = \App\Models\Orden::where('mesa_id', $mesa_id)->latest()->first();

        return view('seguimiento', [
            'estado' => $orden->estado ?? 0,
            'mesa_id' => $mesa_id,
        ]);
    }

    public function pedirCuenta(Request $request)
    {
        $mesa_id = $request->query('mesa_id');

        // Buscar la última orden activa o finalizada (estado 1 en adelante)
        $orden = Orden::where('mesa_id', $mesa_id)
            ->orderByDesc('updated_at')
            ->first();

        // Si la orden existe y aún no se ha solicitado la cuenta (estado < 3), la marcamos como "cuenta solicitada"
        if ($orden && $orden->estado < 3) {
            $orden->estado = 3; // Estado 3 = "Cuenta solicitada"
            $orden->save();
        }

        // Pasamos el estado actual (ya sea 3, 4, etc.) a la vista
        $estado = $orden->estado ?? 0;

        return view('cuenta.confirmacion', compact('mesa_id', 'estado'));
    }


    public function estadoActual($mesa_id)
    {
        $mesa = \App\Models\Mesa::findOrFail($mesa_id);
        return response()->json(['estado' => $mesa->estado]);
    }

    public function nuevas(): JsonResponse
    {
        $cantidad = Orden::where('estado', 0)->count();
        return response()->json(['nuevas' => $cantidad]);
    }

    public function historial()
    {
        $ordenes = Orden::with('mesa')
            ->orderBy('updated_at', 'desc')
            ->get();

        $estados = [
            1 => 'Pendiente',
            2 => 'Entregada',
            3 => 'Cancelada',
            4 => 'Cerrada',
        ];

        return view('historial', compact('ordenes', 'estados'));
    }

    public function generarTicket($ordenId)
    {
        $orden = Orden::findOrFail($ordenId);

        return response()->json([
            'mesa' => $orden->mesa_id,
            'fecha' => $orden->created_at->format('d/m/Y, H:i:s'),
            'productos' => $orden->productos,
            'total' => $orden->total,
        ]);
    }
}
