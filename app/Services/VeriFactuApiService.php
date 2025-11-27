<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class VeriFactuApiService
{
    protected string $baseUrl;
    protected ?string $token = null;
    protected ?string $tokenExpiresAt = null;
    protected bool $isTestMode;

    public function __construct()
    {
        $this->baseUrl = config('verifactu.api_url', 'https://app.verifactuapi.es');
        $this->isTestMode = config('verifactu.test_mode', true);
    }

    /**
     * Autenticarse con el emisor usando username y API key
     *
     * @param string $username NIF del emisor
     * @param string $apiKey API key del emisor
     * @return array
     * @throws Exception
     */
    public function loginEmisor(string $username, string $apiKey): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/api/loginEmisor", [
                'username' => $username,
                'api_key' => $apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->token = $data['token'];
                $this->tokenExpiresAt = $data['expires_at'];

                return [
                    'success' => true,
                    'token' => $this->token,
                    'expires_at' => $this->tokenExpiresAt,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message') ?? 'Error de autenticación',
                'code' => $response->status(),
            ];
        } catch (Exception $e) {
            Log::error('VeriFactu login error: ' . $e->getMessage());
            throw new Exception('Error al conectar con VeriFactu: ' . $e->getMessage());
        }
    }

    /**
     * Establecer el token manualmente (útil si se almacena en BD)
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Verificar si el token está configurado
     */
    public function hasToken(): bool
    {
        return !empty($this->token);
    }

    /**
     * Registrar una factura en VeriFactu/AEAT
     *
     * @param array $facturaData Datos de la factura en formato VeriFactu
     * @return array
     * @throws Exception
     */
    public function registrarFactura(array $facturaData): array
    {
        if (!$this->hasToken()) {
            throw new Exception('No hay token de autenticación. Debes autenticarte primero con loginEmisor().');
        }

        try {
            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/api/alta-registro-facturacion", $facturaData);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data['data'] ?? $data,
                    'message' => $data['message'] ?? 'Factura registrada correctamente',
                ];
            }

            $errorData = $response->json();
            return [
                'success' => false,
                'error' => $errorData['message'] ?? 'Error al registrar factura',
                'code' => $response->status(),
                'details' => $errorData,
            ];
        } catch (Exception $e) {
            Log::error('VeriFactu register invoice error: ' . $e->getMessage());
            throw new Exception('Error al registrar factura en VeriFactu: ' . $e->getMessage());
        }
    }

    /**
     * Validar formato de factura sin registrarla
     *
     * @param array $facturaData
     * @return array
     * @throws Exception
     */
    public function validarFactura(array $facturaData): array
    {
        if (!$this->hasToken()) {
            throw new Exception('No hay token de autenticación.');
        }

        try {
            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/api/alta-registro-facturacion/validar", $facturaData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Formato de factura válido',
                ];
            }

            $errorData = $response->json();
            return [
                'success' => false,
                'error' => $errorData['message'] ?? 'Formato de factura inválido',
                'details' => $errorData,
            ];
        } catch (Exception $e) {
            Log::error('VeriFactu validate invoice error: ' . $e->getMessage());
            throw new Exception('Error al validar factura: ' . $e->getMessage());
        }
    }

    /**
     * Anular una factura registrada
     *
     * @param string $idFactura ID de la factura a anular
     * @param array $anulacionData Datos de la anulación
     * @return array
     * @throws Exception
     */
    public function anularFactura(string $idFactura, array $anulacionData = []): array
    {
        if (!$this->hasToken()) {
            throw new Exception('No hay token de autenticación.');
        }

        try {
            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/api/anulacion-registro-facturacion/{$idFactura}", $anulacionData);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data['data'] ?? $data,
                    'message' => $data['message'] ?? 'Factura anulada correctamente',
                ];
            }

            $errorData = $response->json();
            return [
                'success' => false,
                'error' => $errorData['message'] ?? 'Error al anular factura',
                'code' => $response->status(),
                'details' => $errorData,
            ];
        } catch (Exception $e) {
            Log::error('VeriFactu cancel invoice error: ' . $e->getMessage());
            throw new Exception('Error al anular factura: ' . $e->getMessage());
        }
    }

    /**
     * Obtener lista de facturas registradas
     *
     * @param array $filtros Filtros opcionales (IDEmisorFactura, NumSerieFactura, etc.)
     * @return array
     * @throws Exception
     */
    public function listarFacturas(array $filtros = []): array
    {
        if (!$this->hasToken()) {
            throw new Exception('No hay token de autenticación.');
        }

        try {
            $response = Http::withToken($this->token)
                ->get("{$this->baseUrl}/api/alta-registro-facturacion", $filtros);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'facturas' => $data['data']['items'] ?? [],
                    'total' => $data['data']['count'] ?? 0,
                ];
            }

            return [
                'success' => false,
                'error' => 'Error al obtener facturas',
                'code' => $response->status(),
            ];
        } catch (Exception $e) {
            Log::error('VeriFactu list invoices error: ' . $e->getMessage());
            throw new Exception('Error al listar facturas: ' . $e->getMessage());
        }
    }

    /**
     * Obtener una factura específica por ID
     *
     * @param string $idFactura
     * @return array
     * @throws Exception
     */
    public function obtenerFactura(string $idFactura): array
    {
        if (!$this->hasToken()) {
            throw new Exception('No hay token de autenticación.');
        }

        try {
            $response = Http::withToken($this->token)
                ->get("{$this->baseUrl}/api/alta-registro-facturacion/{$idFactura}");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'factura' => $data['data'] ?? $data,
                ];
            }

            return [
                'success' => false,
                'error' => 'Factura no encontrada',
                'code' => $response->status(),
            ];
        } catch (Exception $e) {
            Log::error('VeriFactu get invoice error: ' . $e->getMessage());
            throw new Exception('Error al obtener factura: ' . $e->getMessage());
        }
    }

    /**
     * Validar destinatarios contra servidores AEAT
     *
     * @param array $destinatarios
     * @return array
     * @throws Exception
     */
    public function validarDestinatarios(array $destinatarios): array
    {
        if (!$this->hasToken()) {
            throw new Exception('No hay token de autenticación.');
        }

        try {
            $response = Http::withToken($this->token)
                ->post("{$this->baseUrl}/api/destinatarios/validar", [
                    'Destinatarios' => $destinatarios,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Error al validar destinatarios',
                'details' => $response->json(),
            ];
        } catch (Exception $e) {
            Log::error('VeriFactu validate recipients error: ' . $e->getMessage());
            throw new Exception('Error al validar destinatarios: ' . $e->getMessage());
        }
    }

    /**
     * Obtener listas de referencia de VeriFactu
     *
     * @return array
     * @throws Exception
     */
    public function obtenerListas(): array
    {
        if (!$this->hasToken()) {
            throw new Exception('No hay token de autenticación.');
        }

        try {
            $response = Http::withToken($this->token)
                ->get("{$this->baseUrl}/api/listas");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'listas' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Error al obtener listas',
            ];
        } catch (Exception $e) {
            Log::error('VeriFactu get lists error: ' . $e->getMessage());
            throw new Exception('Error al obtener listas: ' . $e->getMessage());
        }
    }

    /**
     * Verificar estado del servidor
     *
     * @return array
     */
    public function verificarEstado(): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/api/status");

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Construir estructura de factura en formato VeriFactu
     *
     * @param array $datos Datos simplificados de la factura
     * @return array Estructura completa para la API
     */
    public function construirFactura(array $datos): array
    {
        return [
            'IDEmisorFactura' => $datos['nif_emisor'],
            'NumSerieFactura' => $datos['numero_factura'],
            'FechaExpedicionFactura' => $datos['fecha_expedicion'], // Formato: DD-MM-YYYY
            'TipoFactura' => $datos['tipo_factura'] ?? 'F1', // F1, F2, F3, R1-R5
            'DescripcionOperacion' => $datos['descripcion'],

            // Destinatarios
            'Destinatarios' => $datos['destinatarios'] ?? [],

            // Desglose de IVA
            'Desglose' => $datos['desglose'] ?? [],

            // Totales
            'CuotaTotal' => $datos['cuota_total'],
            'ImporteTotal' => $datos['importe_total'],

            // Opcionales
            'RefExterna' => $datos['ref_externa'] ?? null,
            'tag' => $datos['tag'] ?? null,
            'webhook_id' => $datos['webhook_id'] ?? null,
            'test_production' => $this->isTestMode ? 't' : 'p',
        ];
    }

    /**
     * Construir destinatario español
     *
     * @param string $nif
     * @param string $nombre
     * @return array
     */
    public function construirDestinatarioEspanol(string $nif, string $nombre): array
    {
        return [
            'NIF' => $nif,
            'NombreRazon' => $nombre,
        ];
    }

    /**
     * Construir desglose de IVA
     *
     * @param float $base Base imponible
     * @param float $tipoImpositivo Porcentaje de IVA (ej: 21, 10, 4)
     * @param float $cuotaRepercutida Cuota de IVA
     * @return array
     */
    public function construirDesgloseIVA(float $base, float $tipoImpositivo, float $cuotaRepercutida): array
    {
        return [
            'BaseImponible' => $base,
            'TipoImpositivo' => $tipoImpositivo,
            'CuotaRepercutida' => $cuotaRepercutida,
        ];
    }
}
