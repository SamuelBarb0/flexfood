<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VeriFactuWebhookController extends Controller
{
    /**
     * Manejar webhook de VeriFactu con respuesta AEAT
     *
     * Este webhook se llama cuando la AEAT responde (acepta o rechaza) una factura.
     * Configurar la URL del webhook en el panel de VeriFactu.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        try {
            // Log del payload completo para debugging
            Log::info('VeriFactu Webhook recibido', [
                'payload' => $request->all(),
                'headers' => $request->headers->all(),
            ]);

            // Validar que venga el ID de la factura
            $verifactuId = $request->input('invoice_id') ?? $request->input('id');
            if (!$verifactuId) {
                Log::error('Webhook VeriFactu sin invoice_id');
                return response()->json(['error' => 'Missing invoice_id'], 400);
            }

            // Buscar factura por verifactu_id
            $factura = Factura::where('verifactu_id', $verifactuId)->first();

            if (!$factura) {
                Log::warning('Webhook VeriFactu: factura no encontrada', ['verifactu_id' => $verifactuId]);
                return response()->json(['error' => 'Invoice not found'], 404);
            }

            // Obtener estado AEAT
            $aeatStatus = $request->input('aeat_status') ?? $request->input('status');
            $aeatResponse = $request->input('aeat_response') ?? $request->all();

            // Actualizar factura según respuesta AEAT
            if ($aeatStatus === 'accepted' || $aeatStatus === 'aceptada') {
                $factura->marcarAceptadaAEAT($aeatResponse);

                Log::info('Factura aceptada por AEAT', [
                    'factura_id' => $factura->id,
                    'numero_factura' => $factura->numero_factura,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Factura marcada como aceptada por AEAT',
                ]);
            }

            if ($aeatStatus === 'rejected' || $aeatStatus === 'rechazada') {
                $errorMessage = $request->input('error_message') ??
                               $request->input('aeat_error') ??
                               'Error desconocido de la AEAT';

                $factura->marcarRechazadaAEAT($errorMessage, $aeatResponse);

                Log::warning('Factura rechazada por AEAT', [
                    'factura_id' => $factura->id,
                    'numero_factura' => $factura->numero_factura,
                    'error' => $errorMessage,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Factura marcada como rechazada por AEAT',
                ]);
            }

            // Estado desconocido
            Log::warning('Webhook VeriFactu con estado desconocido', [
                'status' => $aeatStatus,
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unknown AEAT status',
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error procesando webhook VeriFactu', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Verificar el estado de una factura manualmente
     *
     * Este endpoint permite consultar el estado de una factura en VeriFactu
     * (útil si no se recibió el webhook)
     *
     * @param Factura $factura
     * @return \Illuminate\Http\JsonResponse
     */
    public function verificarEstado(Factura $factura)
    {
        if (!$factura->estaEnviada()) {
            return response()->json([
                'error' => 'La factura no ha sido enviada a VeriFactu',
            ], 400);
        }

        // Aquí podrías llamar a VeriFactuApiService::obtenerFactura()
        // para consultar el estado actual en VeriFactu

        return response()->json([
            'factura_id' => $factura->id,
            'numero_factura' => $factura->numero_factura,
            'estado' => $factura->estado,
            'aeat_estado' => $factura->aeat_estado,
            'verifactu_id' => $factura->verifactu_id,
            'fecha_envio' => $factura->fecha_envio_verifactu,
            'aeat_fecha_respuesta' => $factura->aeat_fecha_respuesta,
        ]);
    }
}
