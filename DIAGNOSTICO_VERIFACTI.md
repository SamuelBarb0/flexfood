# 🔍 Diagnóstico: Facturas no aparecen en VeriFacti

## Problema
Las facturas se generan en FlexFood pero no aparecen en el panel de VeriFacti.

---

## ✅ Pasos de Diagnóstico

### 1. Verificar que la facturación automática está habilitada

```sql
SELECT
    id,
    nombre,
    fiscal_habilitado,
    facturacion_automatica,
    verifactu_api_username
FROM restaurantes
WHERE id = TU_ID;
```

**Esperado:**
- `fiscal_habilitado` = 1
- `facturacion_automatica` = 1
- `verifactu_api_username` = Tu NIF

---

### 2. Ejecutar migración de facturación automática

```bash
php artisan migrate
```

Esto creará el campo `facturacion_automatica` en la tabla `restaurantes`.

---

### 3. Revisar logs de errores

```bash
tail -100 storage/logs/laravel.log | grep -i "factura\|verifacti\|error"
```

**Errores comunes:**

#### Error: `foreach() argument must be of type array|object, null given`
**Causa:** El campo `productos_json` no existe en el modelo Orden
**Solución:** ✅ Ya corregido - ahora usa `$orden->productos`

#### Error: `La orden no tiene productos para facturar`
**Causa:** La orden no tiene productos guardados
**Solución:** Verificar que la orden tiene productos

#### Error: `El restaurante no tiene credenciales de VeriFacti`
**Causa:** No se configuraron las credenciales API
**Solución:** Ir a Settings → Fiscal → Credenciales VeriFacti API

---

### 4. Probar generación manual de factura

```bash
php test_facturacion.php
```

Este script:
- Busca un restaurante con facturación habilitada
- Encuentra una orden finalizada
- Genera, emite y envía la factura a VeriFacti
- Muestra el resultado detallado

---

### 5. Verificar facturas en base de datos

```sql
SELECT
    id,
    numero_factura,
    orden_id,
    estado,
    verifactu_id,
    verifactu_qr_url,
    aeat_estado,
    created_at
FROM facturas
ORDER BY id DESC
LIMIT 10;
```

**Estados esperados:**
- `estado` = 'enviada'
- `verifactu_id` = UUID (ej: '123e4567-e89b-12d3-a456-426614174000')
- `aeat_estado` = 'pendiente' (hasta que AEAT responda)

---

### 6. Verificar comunicación con VeriFacti

#### Opción A: Health Check
```bash
php artisan verifacti:test
```

#### Opción B: Tinker
```bash
php artisan tinker
```

```php
$restaurante = App\Models\Restaurante::first();
$service = app(App\Services\VeriFactiService::class);
$service->setApiKey($restaurante->verifactu_api_key);
$result = $service->healthCheck();
print_r($result);
```

**Respuesta esperada:**
```php
[
    'success' => true,
    'data' => [...]
]
```

---

### 7. Probar envío manual a VeriFacti

```bash
php artisan tinker
```

```php
$orden = App\Models\Orden::where('estado', 4)->latest()->first();
$invoiceService = app(App\Services\InvoiceService::class);

// Generar factura
$factura = $invoiceService->generarFacturaDesdeOrden($orden);

// Emitir
$invoiceService->emitirFactura($factura);

// Enviar a VeriFacti
$resultado = $invoiceService->enviarAVeriFactu($factura);

print_r($resultado);
```

---

## 🐛 Errores Comunes y Soluciones

### Error 1: "La factura no está en condiciones de ser enviada"

**Causa:** La factura no está en estado 'emitida'

**Solución:**
```php
$factura->marcarComoEmitida();
$factura->save();
```

---

### Error 2: "El restaurante no tiene credenciales de VeriFacti"

**Verificar:**
```sql
SELECT verifactu_api_username, verifactu_api_key_encrypted
FROM restaurantes
WHERE id = TU_ID;
```

**Solución:**
- Ir a `/r/{slug}/settings` → Fiscal
- Configurar credenciales VeriFacti API

---

### Error 3: "No hay series de facturación"

**Verificar:**
```sql
SELECT * FROM series_facturacion WHERE restaurante_id = TU_ID;
```

**Solución:**
- Crear una serie desde Settings → Fiscal → Series de Facturación

---

### Error 4: VeriFacti responde con error 401 (Unauthorized)

**Causa:** API Key incorrecta o expirada

**Solución:**
1. Verificar API Key en panel de VeriFacti
2. Copiar nueva API Key
3. Actualizar en Settings → Fiscal

---

### Error 5: VeriFacti responde con error 400 (Bad Request)

**Causas posibles:**
- Formato de fecha incorrecto (debe ser DD-MM-YYYY)
- Datos faltantes (serie, número, NIF)
- Más de 12 líneas en la factura

**Verificar formato:**
```php
$datosFactura = [
    'serie' => 'FF',
    'numero' => '1',
    'fecha_expedicion' => '26-11-2025', // DD-MM-YYYY
    'tipo_factura' => 'F2',
    'descripcion' => 'Pedido #123',
    'lineas' => [...], // Máximo 12
    'importe_total' => 55.00,
];
```

---

## 📊 Verificar en VeriFacti

### Si las facturas NO aparecen en VeriFacti pero SÍ en FlexFood:

1. **Verificar credenciales:**
   - Usuario (NIF) correcto
   - API Key válida

2. **Verificar modo TEST:**
   - En `.env`: `VERIFACTU_TEST_MODE=true`
   - En panel VeriFacti: debe estar en modo TEST

3. **Verificar respuesta de VeriFacti:**
   ```sql
   SELECT verifactu_response FROM facturas WHERE id = ULTIMA_FACTURA;
   ```

4. **Revisar logs de Laravel:**
   ```bash
   grep "VeriFacti" storage/logs/laravel.log
   ```

---

## ✅ Checklist de Verificación

- [ ] Migración `facturacion_automatica` ejecutada
- [ ] Campo `facturacion_automatica` = 1 en BD
- [ ] Credenciales VeriFacti configuradas
- [ ] Serie de facturación creada
- [ ] Health check de VeriFacti funciona
- [ ] Facturas se crean en tabla `facturas`
- [ ] Facturas tienen `verifactu_id` (UUID)
- [ ] No hay errores en `storage/logs/laravel.log`

---

## 🔄 Flujo Correcto

```
Finalizar pedido (TPV)
    ↓
OrdenController::finalizar()
    ↓
¿fiscal_habilitado && facturacion_automatica?
    ↓ SÍ
InvoiceService::generarFacturaDesdeOrden()
    ↓
InvoiceService::emitirFactura()
    ↓
InvoiceService::enviarAVeriFactu()
    ↓
VeriFactiService::crearFactura()
    ↓
POST https://api.verifacti.com/verifactu/create
    ↓
VeriFacti responde con UUID + QR
    ↓
Factura guardada con verifactu_id
    ↓
✅ Factura visible en panel VeriFacti
```

---

## 📞 Si nada funciona

1. **Revisar URL de API:**
   ```php
   config('verifactu.api_url'); // Debe ser: https://api.verifacti.com
   ```

2. **Verificar conectividad:**
   ```bash
   curl -I https://api.verifacti.com
   ```

3. **Contactar soporte VeriFacti:**
   - Proporcionar UUID de la factura
   - Mostrar respuesta de error

---

*Última actualización: 26/11/2025*
