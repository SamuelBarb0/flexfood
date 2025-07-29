<?php

namespace App\Http\Controllers;

use App\Models\Orden;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

    public function activar(Orden $orden)
    {
        $orden->estado = 1; // Estado 1: En proceso
        $orden->save();

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
