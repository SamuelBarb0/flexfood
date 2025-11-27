<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Servicio para interactuar con VeriFacti API
 *
 * VeriFacti es un servicio SaaS que facilita el cumplimiento de Verifactu
 * actuando como colaborador social de la AEAT.
 *
 * Documentación: https://www.verifacti.com/docs
 */
class VeriFactiService
{
    protected string $baseUrl;
    protected ?string $apiKey = null;

    public function __construct()
    {
        $this->baseUrl = config('verifactu.api_url', 'https://api.verifacti.com');
    }

    /**
     * Establecer API Key (Bearer token)
     */
    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Verificar salud de la API y validar API key
     *
     * GET /verifactu/health
     *
     * @return array ['success' => bool, 'nif' => string, 'environment' => string]
     */
    public function healthCheck(): array
    {
        if (!$this->apiKey) {
            return [
                'success' => false,
                'error' => 'API Key no configurada',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/health");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'nif' => $data['nif'] ?? null,
                    'environment' => $data['environment'] ?? 'unknown',
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => 'API Key inválida o servicio no disponible',
                'code' => $response->status(),
                'body' => $response->body(),
            ];
        } catch (Exception $e) {
            Log::error('VeriFacti health check error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Error de conexión: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Crear factura en VeriFacti
     *
     * POST /verifactu/create
     *
     * @param array $facturaData Datos de la factura en formato VeriFacti
     * @return array
     * @throws Exception
     */
    public function crearFactura(array $facturaData): array
    {
        if (!$this->apiKey) {
            throw new Exception('API Key no configurada');
        }

        try {
            $url = "{$this->baseUrl}/verifactu/create";

            Log::info('VeriFacti: Enviando factura', [
                'url' => $url,
                'serie' => $facturaData['serie'] ?? null,
                'numero' => $facturaData['numero'] ?? null,
            ]);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post($url, $facturaData);

            // Respuesta exitosa (200)
            if ($response->successful()) {
                $data = $response->json();

                Log::info('VeriFacti: Factura creada exitosamente', [
                    'uuid' => $data['uuid'] ?? null,
                    'estado' => $data['estado'] ?? null,
                ]);

                return [
                    'success' => true,
                    'uuid' => $data['uuid'],
                    'estado' => $data['estado'], // Siempre "Pendiente"
                    'qr_base64' => $data['qr'] ?? null,
                    'qr_url' => $data['enlace_verificacion'] ?? null,
                    'huella' => $data['huella'] ?? null,
                    'data' => $data,
                ];
            }

            // Error de validación (400)
            if ($response->status() === 400) {
                $errorData = $response->json();
                $errorMessage = $errorData['error'] ?? 'Error de validación';

                Log::warning('VeriFacti: Error de validación', [
                    'error' => $errorMessage,
                    'details' => $errorData,
                ]);

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'code' => 400,
                    'details' => $errorData,
                ];
            }

            // Error del servidor (500)
            if ($response->status() === 500) {
                Log::error('VeriFacti: Error del servidor', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Error del servidor VeriFacti. Intenta de nuevo más tarde.',
                    'code' => 500,
                ];
            }

            // Otro error
            Log::error('VeriFacti: Error inesperado', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Error inesperado al crear factura',
                'code' => $response->status(),
                'body' => $response->body(),
            ];

        } catch (Exception $e) {
            Log::error('VeriFacti create invoice exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new Exception('Error al crear factura en VeriFacti: ' . $e->getMessage());
        }
    }

    /**
     * Consultar estado de factura por UUID
     *
     * GET /verifactu/status?uuid={uuid}
     *
     * @param string $uuid UUID de la factura en VeriFacti
     * @return array
     */
    public function consultarEstado(string $uuid): array
    {
        if (!$this->apiKey) {
            return [
                'success' => false,
                'error' => 'API Key no configurada',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])->get("{$this->baseUrl}/verifactu/status", [
                'uuid' => $uuid,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'No se pudo consultar el estado',
                'code' => $response->status(),
            ];
        } catch (Exception $e) {
            Log::error('VeriFacti status query error', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Error al consultar estado: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Listar facturas
     *
     * POST /verifactu/list
     *
     * @param array $filtros Filtros opcionales
     * @return array
     */
    public function listarFacturas(array $filtros = []): array
    {
        if (!$this->apiKey) {
            return [
                'success' => false,
                'error' => 'API Key no configurada',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/verifactu/list", $filtros);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'No se pudo listar facturas',
                'code' => $response->status(),
            ];
        } catch (Exception $e) {
            Log::error('VeriFacti list error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Error al listar facturas: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Construir datos de factura en formato VeriFacti
     *
     * Formato requerido por VeriFacti:
     * - serie (string, max 60 chars combinado con numero)
     * - numero (string)
     * - fecha_expedicion (DD-MM-YYYY)
     * - tipo_factura (F1, F2, F3, R1-R5)
     * - descripcion (1-500 chars)
     * - lineas (array, max 12)
     * - importe_total (float)
     * - nif (opcional para F2)
     * - nombre (opcional para F2)
     *
     * @param array $datos Datos en formato interno
     * @return array Datos en formato VeriFacti
     */
    public function construirFactura(array $datos): array
    {
        // Validar que tenemos los datos mínimos
        if (!isset($datos['numero']) || !isset($datos['fecha_expedicion'])) {
            throw new Exception('Faltan datos obligatorios: numero y fecha_expedicion');
        }

        if (!isset($datos['lineas']) || empty($datos['lineas'])) {
            throw new Exception('La factura debe tener al menos una línea');
        }

        // Limitar a 12 líneas (requisito VeriFacti)
        if (count($datos['lineas']) > 12) {
            throw new Exception('VeriFacti permite máximo 12 líneas por factura');
        }

        $factura = [
            'serie' => $datos['serie'] ?? 'FF',
            'numero' => (string) $datos['numero'],
            'fecha_expedicion' => $datos['fecha_expedicion'], // DD-MM-YYYY
            'tipo_factura' => $datos['tipo_factura'] ?? 'F2', // F2 = Simplificada
            'descripcion' => substr($datos['descripcion'] ?? 'Servicios de restauración', 0, 500),
            'lineas' => $datos['lineas'],
            'importe_total' => (float) $datos['importe_total'],
        ];

        // Añadir destinatario si existe (obligatorio para F1, opcional para F2)
        if (!empty($datos['nif'])) {
            $factura['nif'] = $datos['nif'];
        }

        if (!empty($datos['nombre'])) {
            $factura['nombre'] = $datos['nombre'];
        }

        // Fecha de operación (opcional)
        if (!empty($datos['fecha_operacion'])) {
            $factura['fecha_operacion'] = $datos['fecha_operacion'];
        }

        return $factura;
    }

    /**
     * Construir línea de factura en formato VeriFacti
     *
     * @param float $base Base imponible
     * @param float $tipoIva Porcentaje de IVA (ej: 10, 21)
     * @param float $cuota Cuota de IVA
     * @return array
     */
    public function construirLinea(float $base, float $tipoIva, float $cuota): array
    {
        return [
            'base_imponible' => round($base, 2),
            'tipo_impositivo' => $tipoIva,
            'cuota_repercutida' => round($cuota, 2),
        ];
    }
}
