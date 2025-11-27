<?php

namespace App\Http\Controllers;

use App\Models\Restaurante;
use App\Models\Factura;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FacturaController extends Controller
{
    /**
     * Listado de facturas del restaurante
     */
    public function index(Restaurante $restaurante, Request $request)
    {
        // Filtros
        $estado = $request->get('estado');
        $aeatEstado = $request->get('aeat_estado');
        $desde = $request->get('desde');
        $hasta = $request->get('hasta');

        $query = $restaurante->facturas()->with(['serieFacturacion', 'orden']);

        // Aplicar filtros
        if ($estado) {
            $query->where('estado', $estado);
        }

        if ($aeatEstado) {
            $query->where('aeat_estado', $aeatEstado);
        }

        if ($desde) {
            $query->whereDate('fecha_emision', '>=', $desde);
        }

        if ($hasta) {
            $query->whereDate('fecha_emision', '<=', $hasta);
        }

        // Ordenar por más recientes
        $facturas = $query->orderBy('fecha_emision', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        // Estadísticas
        $estadisticas = [
            'total' => $restaurante->facturas()->count(),
            'pendientes' => $restaurante->facturas()->where('aeat_estado', 'pendiente')->count(),
            'aceptadas' => $restaurante->facturas()->where('aeat_estado', 'aceptada')->count(),
            'rechazadas' => $restaurante->facturas()->where('aeat_estado', 'rechazada')->count(),
            'total_mes' => $restaurante->facturas()
                ->whereMonth('fecha_emision', now()->month)
                ->whereYear('fecha_emision', now()->year)
                ->sum('total_factura'),
        ];

        return view('facturas.index', compact('restaurante', 'facturas', 'estadisticas'));
    }

    /**
     * Ver detalle de una factura
     */
    public function show(Restaurante $restaurante, Factura $factura)
    {
        if ($factura->restaurante_id !== $restaurante->id) {
            abort(404);
        }

        $factura->load(['serieFacturacion', 'lineas', 'orden', 'comercioFiscal']);

        return view('facturas.show', compact('restaurante', 'factura'));
    }

    /**
     * Descargar factura en PDF
     */
    public function descargarPDF(Restaurante $restaurante, Factura $factura)
    {
        if ($factura->restaurante_id !== $restaurante->id) {
            abort(404);
        }

        // TODO: Implementar generación de PDF con DomPDF o similar
        // Por ahora retornar la vista para imprimir
        $factura->load(['serieFacturacion', 'lineas', 'orden', 'comercioFiscal']);

        return view('facturas.pdf', compact('restaurante', 'factura'));
    }

    /**
     * Reenviar factura a VeriFacti
     */
    public function reenviar(Restaurante $restaurante, Factura $factura)
    {
        if ($factura->restaurante_id !== $restaurante->id) {
            abort(404);
        }

        try {
            $invoiceService = app(InvoiceService::class);

            // Verificar que esté emitida
            if ($factura->estado !== 'emitida') {
                return back()->withErrors(['error' => 'Solo se pueden reenviar facturas emitidas.']);
            }

            // Enviar a VeriFacti
            $resultado = $invoiceService->enviarAVeriFactu($factura);

            Log::info('Factura reenviada manualmente', [
                'factura_id' => $factura->id,
                'numero_factura' => $factura->numero_factura,
                'uuid' => $resultado['uuid'] ?? null,
            ]);

            return back()->with('ok', 'Factura reenviada correctamente a VeriFacti.');
        } catch (\Exception $e) {
            Log::error('Error al reenviar factura', [
                'factura_id' => $factura->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Error al reenviar: ' . $e->getMessage()]);
        }
    }

    /**
     * Anular factura
     */
    public function anular(Restaurante $restaurante, Factura $factura)
    {
        if ($factura->restaurante_id !== $restaurante->id) {
            abort(404);
        }

        try {
            // Solo se pueden anular facturas en borrador
            if ($factura->estado !== 'borrador') {
                return back()->withErrors(['error' => 'Solo se pueden anular facturas en borrador.']);
            }

            $factura->update(['estado' => 'anulada']);

            return back()->with('ok', 'Factura anulada correctamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al anular: ' . $e->getMessage()]);
        }
    }
}
