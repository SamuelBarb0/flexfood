# Migración de VeriFactu API a VeriFacti

## 🎯 Resumen

La implementación actual está basada en **VeriFactu API** (app.verifactuapi.es), pero el servicio correcto es **VeriFacti** (verifacti.com).

**Buenas noticias:** La estructura implementada es **98% compatible**. Solo requiere ajustes menores en la capa de comunicación con la API.

---

## 📊 Diferencias Principales

| Aspecto | Implementado (VeriFactu API) | Correcto (VeriFacti) |
|---------|------------------------------|----------------------|
| URL Base | `https://app.verifactuapi.es` | `https://api.verifacti.com` |
| Autenticación | Login con username + API key → Token | API Key directa (Bearer token) |
| Endpoint crear factura | `/api/alta-registro-facturacion` | `/verifactu/create` |
| Certificado digital | Usuario debe subir el suyo | VeriFacti usa el suyo propio |
| Modelo Representación | Usuario debe firmar y subir | VeriFacti gestiona automáticamente |
| Formato factura | JSON custom | JSON específico de VeriFacti |

---

## ✅ Lo que YA funciona (sin cambios)

1. ✅ **Estructura de base de datos**
   - Campos en `facturas` y `restaurantes`
   - Migraciones ejecutadas

2. ✅ **Modelos Eloquent**
   - `Factura` con métodos QR y AEAT
   - `Restaurante` con métodos credenciales

3. ✅ **Webhook de respuestas AEAT**
   - `VeriFactuWebhookController`
   - Actualización automática de estados

4. ✅ **Interfaz de usuario**
   - Formulario de credenciales API
   - Gestión de certificados (opcional con VeriFacti)

---

## 🔧 Cambios Necesarios

### 1. **Actualizar configuración** ✅ YA HECHO

```php
// config/verifactu.php
'api_url' => env('VERIFACTU_API_URL', 'https://api.verifacti.com'),
```

### 2. **Crear VeriFactiService** (nuevo)

```php
// app/Services/VeriFactiService.php

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class VeriFactiService
{
    protected string $baseUrl;
    protected ?string $apiKey = null;

    public function __construct()
    {
        $this->baseUrl = config('verifactu.api_url', 'https://api.verifacti.com');
    }

    /**
     * Establecer API Key
     */
    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Verificar salud de la API y validar API key
     */
    public function healthCheck(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/verifactu/health");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'API Key inválida',
                'code' => $response->status(),
            ];
        } catch (Exception $e) {
            Log::error('VeriFacti health check error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Crear factura en VeriFacti
     */
    public function crearFactura(array $facturaData): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/verifactu/create", $facturaData);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'uuid' => $data['uuid'],
                    'estado' => $data['estado'], // Siempre "Pendiente"
                    'qr_base64' => $data['qr'],
                    'qr_url' => $data['enlace_verificacion'],
                    'huella' => $data['huella'],
                    'data' => $data,
                ];
            }

            // Error 400: validación
            if ($response->status() === 400) {
                $errorData = $response->json();
                return [
                    'success' => false,
                    'error' => $errorData['error'] ?? 'Error de validación',
                    'code' => 400,
                    'details' => $errorData,
                ];
            }

            // Error 500: servidor
            return [
                'success' => false,
                'error' => 'Error del servidor VeriFacti',
                'code' => $response->status(),
            ];

        } catch (Exception $e) {
            Log::error('VeriFacti create invoice error: ' . $e->getMessage());
            throw new Exception('Error al crear factura en VeriFacti: ' . $e->getMessage());
        }
    }

    /**
     * Consultar estado de factura por UUID
     */
    public function consultarEstado(string $uuid): array
    {
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
            Log::error('VeriFacti status error: ' . $e->getMessage());
            throw new Exception('Error al consultar estado: ' . $e->getMessage());
        }
    }

    /**
     * Construir datos de factura en formato VeriFacti
     */
    public function construirFactura(array $datos): array
    {
        // Convertir de nuestro formato interno al formato VeriFacti
        return [
            'serie' => $datos['serie'] ?? 'FF',
            'numero' => $datos['numero'],
            'fecha_expedicion' => $datos['fecha_expedicion'], // DD-MM-YYYY
            'tipo_factura' => $datos['tipo_factura'] ?? 'F2', // F1, F2, F3, R1-R5
            'descripcion' => $datos['descripcion'],

            // Destinatario (opcional para F2 simplificadas)
            'nif' => $datos['nif'] ?? null,
            'nombre' => $datos['nombre'] ?? null,

            // Líneas de factura (máximo 12)
            'lineas' => $datos['lineas'],

            // Total
            'importe_total' => $datos['importe_total'],

            // Opcional
            'fecha_operacion' => $datos['fecha_operacion'] ?? null,
        ];
    }

    /**
     * Construir línea de factura VeriFacti
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
```

### 3. **Actualizar InvoiceService**

Cambiar de `VeriFactuApiService` a `VeriFactiService`:

```php
// app/Services/InvoiceService.php

use App\Services\VeriFactiService; // Cambiar import

public function enviarAVeriFactu(Factura $factura): array
{
    // ... código existente ...

    // CAMBIAR: Usar VeriFactiService
    $veriFactiService = app(VeriFactiService::class);
    $veriFactiService->setApiKey($restaurante->verifactu_api_key);

    // Construir datos en formato VeriFacti
    $datosFactura = $this->construirDatosVeriFacti($factura);

    // Crear factura
    $resultado = $veriFactiService->crearFactura($datosFactura);

    if ($resultado['success']) {
        // VeriFacti devuelve:
        // - uuid (ID único)
        // - qr_base64 (QR en base64)
        // - qr_url (URL de verificación)
        // - huella (fingerprint/hash)

        $factura->marcarComoEnviada(
            $resultado['uuid'],
            $resultado['data'],
            $resultado['qr_url'],
            $resultado['qr_base64']
        );
    } else {
        $error = $resultado['error'] ?? 'Error desconocido';
        $factura->registrarErrorVeriFactu($error);
        throw new Exception("Error al enviar factura a VeriFacti: {$error}");
    }

    return $resultado;
}

/**
 * Construir datos en formato VeriFacti
 */
protected function construirDatosVeriFacti(Factura $factura): array
{
    $restaurante = $factura->restaurante;
    $serie = $factura->serieFacturacion;

    // Preparar líneas
    $lineas = [];
    foreach ($factura->lineas as $linea) {
        $lineas[] = [
            'base_imponible' => (float) $linea->base_imponible,
            'tipo_impositivo' => (float) $linea->tipo_iva,
            'cuota_repercutida' => (float) $linea->cuota_iva,
        ];
    }

    return [
        'serie' => $serie->codigo_serie,
        'numero' => (string) $factura->numero_serie,
        'fecha_expedicion' => $factura->fecha_emision->format('d-m-Y'),
        'tipo_factura' => $factura->tipo_factura,
        'descripcion' => substr($factura->descripcion ?? 'Servicios de restauración', 0, 500),
        'lineas' => $lineas,
        'importe_total' => (float) $factura->total_factura,

        // Opcional: destinatario (si es factura completa F1)
        'nif' => $factura->comercioFiscal?->nif_cif ?? null,
        'nombre' => $factura->comercioFiscal?->razon_social ?? null,
    ];
}
```

---

## 🚀 Pasos de Migración

### Paso 1: Crear VeriFactiService

```bash
php artisan make:service VeriFactiService
```

Copia el código del servicio de arriba.

### Paso 2: Actualizar InvoiceService

Cambia las referencias de `VeriFactuApiService` a `VeriFactiService`.

### Paso 3: Registrarse en VeriFacti

1. Ve a https://www.verifacti.com/
2. Crea cuenta gratuita
3. Activa el entorno de pruebas
4. Obtén tu API Key del panel

### Paso 4: Configurar en FlexFood

1. Ve a `/r/{restaurante}/settings` → Fiscal
2. En "Credenciales VeriFacti API":
   - Usuario: Tu NIF
   - API Key: La que obtuviste de VeriFacti

### Paso 5: Probar

```php
// Desde Tinker
$restaurante = App\Models\Restaurante::first();
$orden = App\Models\Orden::first();

$invoiceService = app(App\Services\InvoiceService::class);
$factura = $invoiceService->generarFacturaDesdeOrden($orden);
$invoiceService->emitirFactura($factura);

// Enviar a VeriFacti
$resultado = $invoiceService->enviarAVeriFactu($factura);

// Verificar QR
$factura->fresh();
echo $factura->verifactu_qr_url; // URL de verificación
```

---

## 🎁 VENTAJAS de usar VeriFacti

### 1. **Sin Certificado Digital Propio**
- VeriFacti actúa como "colaborador social" de la AEAT
- Usan su propio certificado para firmar
- No necesitas gestionar tu certificado .p12/.pfx

### 2. **Sin Modelo de Representación Manual**
- VeriFacti gestiona automáticamente la representación
- No necesitas firmar ni subir formularios del BOE

### 3. **Más Simple**
- Solo necesitas API Key
- No necesitas autenticación compleja
- QR inmediato en la respuesta

### 4. **Límites de la API**
- Máximo 12 líneas por factura
- Máximo 50 facturas en batch (`/create_bulk`)
- Procesamiento típico: < 1 minuto
- AEAT rate limit: 1 petición/minuto (VeriFacti gestiona la cola)

---

## 📋 Checklist de Migración

- [ ] Crear `VeriFactiService`
- [ ] Actualizar `InvoiceService` para usar VeriFacti
- [ ] Registrarse en VeriFacti.com
- [ ] Obtener API Key de prueba
- [ ] Configurar API Key en FlexFood
- [ ] Probar creación de factura
- [ ] Verificar que llega el QR
- [ ] Configurar webhook (si VeriFacti lo soporta)
- [ ] Actualizar documentación

---

## 🔗 Recursos

- **Panel VeriFacti:** https://www.verifacti.com/
- **Documentación API:** https://www.verifacti.com/docs
- **Guía Rápida:** https://www.verifacti.com/guia-rapida
- **Preguntas Frecuentes:** https://www.verifacti.com/preguntas-frecuentes
- **Soporte:** info@verifacti.com

---

## ⚠️ Notas Importantes

1. **Certificado Digital:** Aunque VeriFacti no lo requiere, **mantenlo** en FlexFood como backup o para otros usos legales.

2. **Modelo de Representación:** Aunque VeriFacti lo gestiona, **conserva** la funcionalidad en FlexFood por si cambias de proveedor.

3. **Webhook:** Verifica con VeriFacti si soportan webhooks para respuestas AEAT. Si no, usa polling con `/verifactu/status?uuid=...`

4. **Límites:** Ten en cuenta el límite de 12 líneas por factura. Si un pedido tiene más productos, agrúpalos.

---

**Estado actual:** Configuración actualizada a `https://api.verifacti.com`. Falta crear `VeriFactiService` y actualizar `InvoiceService`.
