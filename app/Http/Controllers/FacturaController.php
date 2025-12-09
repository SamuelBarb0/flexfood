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

    /**
     * Generar factura con datos del cliente (Factura Completa)
     */
    public function generarConCliente(Restaurante $restaurante, Request $request)
    {
        try {
            // Validar request
            $validated = $request->validate([
                'orden_id' => 'required|exists:ordenes,id',
                'comercio_fiscal' => 'required|array',
                'comercio_fiscal.nif_cif' => 'required|string',
                'comercio_fiscal.razon_social' => 'required|string',
                'comercio_fiscal.direccion' => 'required|string',
                'comercio_fiscal.municipio' => 'required|string',
                'comercio_fiscal.provincia' => 'required|string',
                'comercio_fiscal.codigo_postal' => 'required|string',
                'comercio_fiscal.email' => 'nullable|email',
                'comercio_fiscal.pais' => 'nullable|string',
                'serie_facturacion_id' => 'nullable|exists:series_facturacion,id',
            ]);

            // Obtener la orden
            $orden = \App\Models\Orden::findOrFail($validated['orden_id']);

            // Verificar que la orden pertenece al restaurante
            if ($orden->restaurante_id !== $restaurante->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'La orden no pertenece a este restaurante'
                ], 403);
            }

            // Buscar o crear el comercio fiscal
            $comercioFiscal = \App\Models\ComercioFiscal::firstOrCreate(
                [
                    'restaurante_id' => $restaurante->id,
                    'nif_cif' => $validated['comercio_fiscal']['nif_cif'],
                ],
                array_merge($validated['comercio_fiscal'], [
                    'restaurante_id' => $restaurante->id,
                    'activo' => true,
                ])
            );

            // Generar la factura usando InvoiceService
            $invoiceService = app(InvoiceService::class);

            $opcionesFactura = [
                'tipo_factura' => 'F1', // Factura Completa (con datos del cliente)
                'comercio_fiscal_id' => $comercioFiscal->id,
            ];

            // Si se especificó una serie, usarla
            if (!empty($validated['serie_facturacion_id'])) {
                $opcionesFactura['serie_facturacion_id'] = $validated['serie_facturacion_id'];
            }

            $factura = $invoiceService->generarFacturaDesdeOrden($orden, $opcionesFactura);

            // Emitir factura automáticamente
            $invoiceService->emitirFactura($factura);

            // Enviar a VeriFactu solo si tiene credenciales configuradas
            $verifactuEnviado = false;
            if ($restaurante->tieneCredencialesVeriFactu()) {
                try {
                    $resultado = $invoiceService->enviarAVeriFactu($factura);
                    $verifactuEnviado = true;

                    // IMPORTANTE: Refrescar la factura para obtener los datos actualizados de VeriFactu
                    // (QR, UUID, hash, timestamp, etc.)
                    $factura->refresh();

                    Log::info('Factura enviada a VeriFactu correctamente', [
                        'factura_id' => $factura->id,
                        'uuid' => $factura->verifactu_id,
                        'qr_disponible' => !empty($factura->verifactu_qr_url),
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Error al enviar a VeriFactu', [
                        'factura_id' => $factura->id,
                        'error' => $e->getMessage(),
                    ]);
                    // Continuar aunque falle VeriFactu - la factura ya está creada
                }
            } else {
                Log::info('VeriFactu no configurado, factura generada sin envío', [
                    'factura_id' => $factura->id,
                    'fiscal_habilitado' => $restaurante->fiscal_habilitado,
                ]);
            }

            // Si hay email, enviar la factura
            if (!empty($comercioFiscal->email)) {
                try {
                    // TODO: Implementar envío por email
                    Log::info('Pendiente: Enviar factura por email', [
                        'factura_id' => $factura->id,
                        'email' => $comercioFiscal->email,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Error al enviar email (no crítico)', [
                        'factura_id' => $factura->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'factura' => [
                    'id' => $factura->id,
                    'numero_factura' => $factura->numero_factura,
                    'total' => $factura->total,
                    'qr_url' => $factura->verifactu_qr_url,
                    'verifactu_enviado' => $verifactuEnviado,
                ],
                'message' => $verifactuEnviado
                    ? 'Factura generada y enviada a VeriFactu correctamente'
                    : 'Factura generada correctamente (pendiente envío a VeriFactu)',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error al generar factura con cliente', [
                'restaurante_id' => $restaurante->id,
                'request' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar la factura: ' . $e->getMessage(),
            ], 500);
        }
    }
}
