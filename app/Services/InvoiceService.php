<?php

namespace App\Services;

use App\Models\Factura;
use App\Models\FacturaLinea;
use App\Models\Orden;
use App\Models\Restaurante;
use App\Models\SerieFacturacion;
use App\Models\ComercioFiscal;
use App\Services\VeriFactiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class InvoiceService
{
    /**
     * Generar factura desde una orden
     *
     * @param Orden $orden
     * @param array $opciones Opciones adicionales (tipo_factura, comercio_fiscal_id, etc.)
     * @return Factura
     * @throws Exception
     */
    public function generarFacturaDesdeOrden(Orden $orden, array $opciones = []): Factura
    {
        return DB::transaction(function () use ($orden, $opciones) {
            $restaurante = $orden->restaurante;

            // Verificar que el restaurante tenga habilitada la facturación
            if (!$restaurante->fiscal_habilitado) {
                throw new Exception('El restaurante no tiene habilitada la facturación VeriFactu');
            }

            // Obtener serie de facturación
            $serieId = $opciones['serie_facturacion_id'] ?? null;
            if ($serieId) {
                $serie = SerieFacturacion::where('id', $serieId)
                    ->where('restaurante_id', $restaurante->id)
                    ->where('activa', true)
                    ->firstOrFail();
            } else {
                $serie = $restaurante->seriePrincipal()
                    ->where('activa', true)
                    ->firstOrFail();
            }

            // Obtener siguiente número de factura
            $numeroFactura = $serie->obtenerSiguienteNumero();

            // Crear factura
            $factura = new Factura([
                'restaurante_id' => $restaurante->id,
                'serie_facturacion_id' => $serie->id,
                'orden_id' => $orden->id,
                'comercio_fiscal_id' => $opciones['comercio_fiscal_id'] ?? null,
                'numero_factura' => $numeroFactura,
                'numero_serie' => $serie->ultimo_numero,
                'fecha_emision' => $opciones['fecha_emision'] ?? now()->toDateString(),
                'fecha_operacion' => $opciones['fecha_operacion'] ?? $orden->created_at->toDateString(),
                'tipo_factura' => $opciones['tipo_factura'] ?? 'F2', // F2 = Factura Simplificada
                'descripcion' => $opciones['descripcion'] ?? "Pedido #{$orden->id} - Mesa {$orden->mesa->numero_mesa}",
            ]);

            $factura->save();

            // Crear líneas de factura desde productos de la orden
            $productos = $orden->productos ?? [];

            if (empty($productos)) {
                throw new Exception('La orden no tiene productos para facturar');
            }

            foreach ($productos as $index => $producto) {
                $linea = FacturaLinea::crearDesdeProductoOrden($producto, $index + 1);
                $linea->factura_id = $factura->id;
                $linea->save();
            }

            // Calcular totales
            $factura->load('lineas');
            $factura->calcularTotales();
            $factura->save();

            // Refrescar factura para asegurar que tenemos todos los valores de BD
            $factura->refresh();

            return $factura;
        });
    }

    /**
     * Generar factura manualmente (sin orden asociada)
     *
     * @param Restaurante $restaurante
     * @param array $lineas Array de líneas de factura
     * @param array $opciones Opciones adicionales
     * @return Factura
     * @throws Exception
     */
    public function generarFacturaManual(Restaurante $restaurante, array $lineas, array $opciones = []): Factura
    {
        return DB::transaction(function () use ($restaurante, $lineas, $opciones) {
            // Verificar que el restaurante tenga habilitada la facturación
            if (!$restaurante->fiscal_habilitado) {
                throw new Exception('El restaurante no tiene habilitada la facturación VeriFactu');
            }

            // Obtener serie de facturación
            $serieId = $opciones['serie_facturacion_id'] ?? null;
            if ($serieId) {
                $serie = SerieFacturacion::where('id', $serieId)
                    ->where('restaurante_id', $restaurante->id)
                    ->where('activa', true)
                    ->firstOrFail();
            } else {
                $serie = $restaurante->seriePrincipal()
                    ->where('activa', true)
                    ->firstOrFail();
            }

            // Obtener siguiente número de factura
            $numeroFactura = $serie->obtenerSiguienteNumero();

            // Crear factura
            $factura = new Factura([
                'restaurante_id' => $restaurante->id,
                'serie_facturacion_id' => $serie->id,
                'comercio_fiscal_id' => $opciones['comercio_fiscal_id'] ?? null,
                'numero_factura' => $numeroFactura,
                'numero_serie' => $serie->ultimo_numero,
                'fecha_emision' => $opciones['fecha_emision'] ?? now()->toDateString(),
                'fecha_operacion' => $opciones['fecha_operacion'] ?? now()->toDateString(),
                'tipo_factura' => $opciones['tipo_factura'] ?? 'F1',
                'descripcion' => $opciones['descripcion'] ?? null,
                'observaciones' => $opciones['observaciones'] ?? null,
            ]);

            $factura->save();

            // Crear líneas de factura
            foreach ($lineas as $index => $lineaData) {
                $linea = new FacturaLinea([
                    'factura_id' => $factura->id,
                    'orden' => $index + 1,
                    'descripcion' => $lineaData['descripcion'],
                    'descripcion_adicional' => $lineaData['descripcion_adicional'] ?? null,
                    'cantidad' => $lineaData['cantidad'] ?? 1,
                    'precio_unitario' => $lineaData['precio_unitario'],
                    'tipo_iva' => $lineaData['tipo_iva'] ?? config('verifactu.iva_restauracion', 10),
                    'tipo_recargo' => $lineaData['tipo_recargo'] ?? 0,
                ]);

                if (isset($lineaData['descuento_porcentaje'])) {
                    $linea->descuento_porcentaje = $lineaData['descuento_porcentaje'];
                } elseif (isset($lineaData['descuento_importe'])) {
                    $linea->descuento_importe = $lineaData['descuento_importe'];
                }

                $linea->calcularImportes();
                $linea->save();
            }

            // Calcular totales
            $factura->load('lineas');
            $factura->calcularTotales();
            $factura->save();

            // Refrescar factura para asegurar que tenemos todos los valores de BD
            $factura->refresh();

            return $factura;
        });
    }

    /**
     * Emitir factura (cambiar estado de borrador a emitida)
     *
     * @param Factura $factura
     * @return bool
     * @throws Exception
     */
    public function emitirFactura(Factura $factura): bool
    {
        if ($factura->estado !== 'borrador') {
            throw new Exception('Solo se pueden emitir facturas en estado borrador');
        }

        if ($factura->lineas()->count() === 0) {
            throw new Exception('La factura debe tener al menos una línea');
        }

        if ($factura->total_factura <= 0) {
            throw new Exception('El total de la factura debe ser mayor a 0');
        }

        $factura->marcarComoEmitida();

        return true;
    }

    /**
     * Enviar factura a VeriFacti
     *
     * @param Factura $factura
     * @return array
     * @throws Exception
     */
    public function enviarAVeriFactu(Factura $factura): array
    {
        if (!$factura->puedeEnviarse()) {
            throw new Exception('La factura no está en condiciones de ser enviada');
        }

        $restaurante = $factura->restaurante;

        // Verificar credenciales de VeriFacti
        if (!$restaurante->tieneCredencialesVeriFactu()) {
            throw new Exception('El restaurante no tiene configuradas las credenciales de VeriFacti. Ve a Configuración → Fiscal.');
        }

        // Instanciar servicio de VeriFacti
        $veriFactiService = app(VeriFactiService::class);

        // Establecer API Key (VeriFacti usa Bearer token directo, no login)
        $apiKey = $restaurante->verifactu_api_key;
        $veriFactiService->setApiKey($apiKey);

        // Construir datos de factura en formato VeriFacti
        $datosFactura = $this->construirDatosVeriFacti($factura);

        // Crear factura en VeriFacti
        $resultado = $veriFactiService->crearFactura($datosFactura);

        if ($resultado['success']) {
            // VeriFacti devuelve:
            // - uuid: ID único de la factura
            // - qr_base64: QR en base64
            // - qr_url: URL de verificación (enlace_verificacion)
            // - huella: fingerprint/hash de encadenamiento

            $uuid = $resultado['uuid'];
            $qrBase64 = $resultado['qr_base64'];
            $qrUrl = $resultado['qr_url'];
            $huella = $resultado['huella'] ?? null;

            // Si no obtuvimos QR o huella en la respuesta inicial, consultar el estado inmediatamente
            if (empty($qrBase64) || empty($huella)) {
                try {
                    Log::info('VeriFactu: Consultando estado inmediatamente para obtener QR/Huella', [
                        'uuid' => $uuid,
                    ]);

                    $estadoResultado = $veriFactiService->consultarEstado($uuid);

                    if ($estadoResultado['success']) {
                        $datosActualizados = $estadoResultado['data'];

                        // Usar los datos del estado si están disponibles
                        $qrBase64 = $qrBase64 ?: ($datosActualizados['qr'] ?? null);
                        $qrUrl = $qrUrl ?: ($datosActualizados['enlace_verificacion'] ?? null);
                        $huella = $huella ?: ($datosActualizados['huella'] ?? null);

                        Log::info('VeriFactu: Datos actualizados desde consulta de estado', [
                            'uuid' => $uuid,
                            'qr_obtenido' => !empty($qrBase64),
                            'huella_obtenida' => !empty($huella),
                        ]);
                    }
                } catch (\Exception $e) {
                    // Si falla la consulta de estado, solo logear pero no bloquear
                    Log::warning('VeriFactu: No se pudo consultar estado inmediatamente', [
                        'uuid' => $uuid,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Marcar como enviada con los datos más completos posibles
            $factura->marcarComoEnviada(
                $uuid,
                $resultado['data'] ?? null,
                $qrUrl,
                $qrBase64,
                $huella
            );

            Log::info('VeriFactu: Factura marcada como enviada', [
                'factura_id' => $factura->id,
                'uuid' => $uuid,
                'tiene_qr' => !empty($qrBase64),
                'tiene_huella' => !empty($huella),
            ]);
        } else {
            // Registrar error
            $error = $resultado['error'] ?? 'Error desconocido';
            $factura->registrarErrorVeriFactu($error);
            throw new Exception("Error al enviar factura a VeriFacti: {$error}");
        }

        return $resultado;
    }

    /**
     * Construir datos de factura en formato VeriFacti
     *
     * Formato VeriFacti:
     * - serie, numero, fecha_expedicion (DD-MM-YYYY)
     * - tipo_factura (F1, F2, F3, R1-R5)
     * - descripcion, lineas (max 12), importe_total
     * - nif, nombre (opcionales para F2)
     *
     * @param Factura $factura
     * @return array
     */
    protected function construirDatosVeriFacti(Factura $factura): array
    {
        $serie = $factura->serieFacturacion;

        // Preparar líneas de factura (máximo 12)
        $lineas = [];
        foreach ($factura->lineas as $linea) {
            $lineas[] = [
                'base_imponible' => (float) $linea->base_imponible,
                'tipo_impositivo' => (float) $linea->tipo_iva,
                'cuota_repercutida' => (float) $linea->cuota_iva,
            ];

            // VeriFacti permite máximo 12 líneas
            if (count($lineas) >= 12) {
                break;
            }
        }

        $datos = [
            'serie' => $serie->codigo_serie,
            'numero' => (string) $factura->numero_serie,
            'fecha_expedicion' => $factura->fecha_emision->format('d-m-Y'), // DD-MM-YYYY
            'tipo_factura' => $factura->tipo_factura,
            'descripcion' => substr($factura->descripcion ?? 'Servicios de restauración', 0, 500),
            'lineas' => $lineas,
            'importe_total' => (float) $factura->total_factura,
        ];

        // Añadir destinatario si existe (obligatorio para F1, opcional para F2)
        if ($factura->comercioFiscal) {
            if ($factura->comercioFiscal->nif_cif) {
                $datos['nif'] = $factura->comercioFiscal->nif_cif;
            }
            if ($factura->comercioFiscal->razon_social) {
                $datos['nombre'] = $factura->comercioFiscal->razon_social;
            }
        }

        // Fecha de operación (opcional, si es diferente a la de expedición)
        if ($factura->fecha_operacion && !$factura->fecha_operacion->isSameDay($factura->fecha_emision)) {
            $datos['fecha_operacion'] = $factura->fecha_operacion->format('d-m-Y');
        }

        return $datos;
    }

    /**
     * Crear factura rectificativa
     *
     * @param Factura $facturaOriginal
     * @param string $tipoRectificativa R1-R5
     * @param string $motivo
     * @return Factura
     * @throws Exception
     */
    public function crearFacturaRectificativa(Factura $facturaOriginal, string $tipoRectificativa, string $motivo): Factura
    {
        if (!in_array($tipoRectificativa, ['R1', 'R2', 'R3', 'R4', 'R5'])) {
            throw new Exception('Tipo de factura rectificativa inválido');
        }

        if (!$facturaOriginal->estaEnviada()) {
            throw new Exception('Solo se pueden rectificar facturas enviadas a VeriFactu');
        }

        return DB::transaction(function () use ($facturaOriginal, $tipoRectificativa, $motivo) {
            $restaurante = $facturaOriginal->restaurante;
            $serie = $facturaOriginal->serieFacturacion;

            // Obtener siguiente número
            $numeroFactura = $serie->obtenerSiguienteNumero();

            // Crear factura rectificativa
            $facturaRectificativa = new Factura([
                'restaurante_id' => $restaurante->id,
                'serie_facturacion_id' => $serie->id,
                'comercio_fiscal_id' => $facturaOriginal->comercio_fiscal_id,
                'numero_factura' => $numeroFactura,
                'numero_serie' => $serie->ultimo_numero,
                'fecha_emision' => now()->toDateString(),
                'tipo_factura' => $tipoRectificativa,
                'factura_rectificada_id' => $facturaOriginal->id,
                'motivo_rectificacion' => $motivo,
                'descripcion' => "Rectificación de factura {$facturaOriginal->numero_factura}: {$motivo}",
            ]);

            $facturaRectificativa->save();

            // Copiar líneas (con importes negativos para tipos R1-R4)
            if (in_array($tipoRectificativa, ['R1', 'R2', 'R3', 'R4'])) {
                foreach ($facturaOriginal->lineas as $lineaOriginal) {
                    $linea = $lineaOriginal->replicate();
                    $linea->factura_id = $facturaRectificativa->id;
                    $linea->cantidad = -abs($linea->cantidad); // Negativo
                    $linea->calcularImportes();
                    $linea->save();
                }
            }

            // Calcular totales
            $facturaRectificativa->load('lineas');
            $facturaRectificativa->calcularTotales();
            $facturaRectificativa->save();

            return $facturaRectificativa;
        });
    }

    /**
     * Obtener resumen de facturación por período
     *
     * @param Restaurante $restaurante
     * @param string $desde Fecha desde (Y-m-d)
     * @param string $hasta Fecha hasta (Y-m-d)
     * @return array
     */
    public function resumenPorPeriodo(Restaurante $restaurante, string $desde, string $hasta): array
    {
        $facturas = Factura::where('restaurante_id', $restaurante->id)
            ->whereIn('estado', ['emitida', 'enviada'])
            ->whereBetween('fecha_emision', [$desde, $hasta])
            ->get();

        $totalFacturas = $facturas->count();
        $totalBase = $facturas->sum('base_imponible');
        $totalIva = $facturas->sum('total_iva');
        $totalFacturado = $facturas->sum('total_factura');

        $enviadasVeriFactu = $facturas->where('estado', 'enviada')->count();
        $pendientesEnvio = $facturas->where('estado', 'emitida')->count();

        return [
            'total_facturas' => $totalFacturas,
            'base_imponible' => round($totalBase, 2),
            'total_iva' => round($totalIva, 2),
            'total_facturado' => round($totalFacturado, 2),
            'enviadas_verifactu' => $enviadasVeriFactu,
            'pendientes_envio' => $pendientesEnvio,
            'periodo' => [
                'desde' => $desde,
                'hasta' => $hasta,
            ],
        ];
    }
}
