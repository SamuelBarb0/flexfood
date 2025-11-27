# 🧪 Cómo Probar VeriFacti en FlexFood

## ✅ Pre-requisitos completados:
- [x] Migraciones ejecutadas
- [x] VeriFactiService implementado
- [x] Webhook configurado

---

## 📝 PLAN DE PRUEBAS

### **Opción 1: Prueba SIN VeriFacti (Solo Base de Datos)** ⭐ RECOMENDADO

Esta prueba verifica que toda la estructura esté correcta sin necesitar credenciales de VeriFacti.

#### 1. Acceder a la configuración fiscal

```
URL: http://localhost/flexfood/r/{tu-restaurante-slug}/settings
Tab: Fiscal
```

#### 2. Verificar que aparecen las nuevas secciones:

**Sección 1: Credenciales VeriFacti API**
- [ ] Aparece el formulario con campos:
  - Usuario (NIF)
  - API Key
  - Link a https://www.verifacti.com/

**Sección 2: Certificado Digital (Opcional)**
- [ ] Indica que es opcional con VeriFacti

**Sección 3: Series de Facturación** (ya existía)
- [ ] Sigue funcionando como antes

#### 3. Probar guardado de credenciales:

```
1. Rellena el formulario de Credenciales VeriFacti API:
   - Usuario: B12345678 (NIF ficticio)
   - API Key: test_api_key_1234567890abcdef

2. Haz clic en "Guardar credenciales"

3. Deberías ver mensaje de éxito:
   ✅ "Credenciales de VeriFacti guardadas correctamente."

4. Verifica en la base de datos:
```

```sql
SELECT
    id,
    nombre,
    verifactu_api_username,
    verifactu_api_key_encrypted,
    modelo_representacion_firmado
FROM restaurantes
WHERE id = TU_ID_RESTAURANTE;
```

**Resultado esperado:**
- `verifactu_api_username` = "B12345678"
- `verifactu_api_key_encrypted` = (texto encriptado largo)
- `modelo_representacion_firmado` = 0 (false)

---

### **Opción 2: Prueba CON VeriFacti (Cuenta Real)** 🔐

Si quieres probar el flujo completo con VeriFacti real:

#### PASO 1: Registrarse en VeriFacti

```
URL: https://www.verifacti.com/
```

1. Crea una cuenta
2. Activa el modo TEST (muy importante)
3. Obtén tus credenciales:
   - Usuario (tu NIF)
   - API Key (se genera en el panel)

#### PASO 2: Configurar en FlexFood

1. Accede a `/r/{tu-restaurante}/settings` → Pestaña Fiscal

2. **Datos Fiscales Básicos:**
   - Razón Social: Tu empresa
   - NIF: Tu NIF real (mismo que usaste en VeriFacti)
   - Dirección fiscal completa
   - Régimen IVA: Seleccionar uno

3. **Credenciales VeriFacti API:**
   - Usuario: Tu NIF
   - API Key: La que copiaste de VeriFacti

4. **Certificado Digital:** (OPCIONAL con VeriFacti)
   - VeriFacti usa su propio certificado para firmar
   - Solo necesitas subir el tuyo si quieres firma propia

5. **Serie de Facturación:**
   - Créala manualmente desde la interfaz

#### PASO 3: Configurar Webhook en VeriFacti

```
1. Panel VeriFacti → Configuración → Webhooks
2. Añadir nueva URL de webhook:

   Si estás en local (con ngrok o similar):
   https://tu-ngrok-url.ngrok.io/webhooks/verifactu

   Si estás en producción:
   https://tudominio.com/webhooks/verifactu

3. Guardar
```

#### PASO 4: Probar comando de test

```bash
php artisan verifacti:test
```

Este comando verifica:
- Credenciales configuradas
- Conexión con VeriFacti API
- Health check
- Configuración de facturación
- Estructura de facturas

#### PASO 5: Probar emisión de factura

```php
// Esto lo puedes hacer desde Tinker o creando una ruta de prueba

php artisan tinker

// Obtener tu restaurante
$restaurante = App\Models\Restaurante::find(1);

// Verificar credenciales
$restaurante->tieneCredencialesVeriFactu(); // debe devolver true

// Crear una orden de prueba (si no tienes)
$orden = App\Models\Orden::where('restaurante_id', 1)->first();

// Generar factura
$invoiceService = app(App\Services\InvoiceService::class);
$factura = $invoiceService->generarFacturaDesdeOrden($orden);

// Verificar factura creada
$factura->numero_factura; // FF-000001-2025 (o similar)
$factura->estado; // "borrador"

// Emitir factura
$invoiceService->emitirFactura($factura);
$factura->estado; // "emitida"

// Enviar a VeriFactu
$resultado = $invoiceService->enviarAVeriFactu($factura);

// Verificar resultado
print_r($resultado);

// Verificar que se guardó el QR
$factura->fresh(); // Recargar desde DB
$factura->verifactu_qr_url; // Debería tener una URL
$factura->aeat_estado; // "pendiente"
```

#### PASO 6: Verificar que llega el Webhook

Después de 1-5 minutos, VeriFacti enviará el webhook cuando AEAT responda.

**Ver logs:**
```bash
tail -f storage/logs/laravel.log | grep VeriFacti
```

**Deberías ver:**
```
VeriFacti Webhook recibido
Factura aceptada por AEAT (o rechazada si hay error)
```

**Verificar en base de datos:**
```sql
SELECT
    numero_factura,
    estado,
    verifactu_qr_url,
    aeat_estado,
    aeat_fecha_respuesta
FROM facturas
ORDER BY id DESC
LIMIT 1;
```

**Resultado esperado:**
- `estado` = "enviada"
- `verifactu_qr_url` = URL válida
- `aeat_estado` = "aceptada" (si todo OK) o "rechazada" (si hubo error)
- `aeat_fecha_respuesta` = timestamp actual

---

### **Opción 3: Probar Webhook Manualmente** 🔧

Puedes simular un webhook de VeriFacti usando curl o Postman:

```bash
curl -X POST http://localhost/flexfood/webhooks/verifactu \
  -H "Content-Type: application/json" \
  -d '{
    "invoice_id": "ID_DE_TU_FACTURA_EN_VERIFACTU",
    "aeat_status": "accepted",
    "aeat_response": {
      "csv": "ABC123456789",
      "fecha_registro": "2025-11-26T22:30:00Z"
    }
  }'
```

**Cambiar:**
- `invoice_id`: El `verifactu_id` de una factura tuya
- `aeat_status`: "accepted" o "rejected"

**Verificar logs:**
```bash
tail -f storage/logs/laravel.log
```

**Deberías ver:**
```
[INFO] VeriFacti Webhook recibido
[INFO] Factura aceptada por AEAT
```

---

## 🔍 Verificaciones de Base de Datos

### Verificar nuevos campos en `facturas`:

```sql
DESCRIBE facturas;
```

Debes ver:
- `verifactu_qr_url` (text)
- `verifactu_qr_data` (text)
- `aeat_estado` (enum: pendiente, aceptada, rechazada)
- `aeat_response` (text)
- `aeat_fecha_respuesta` (timestamp)

### Verificar nuevos campos en `restaurantes`:

```sql
DESCRIBE restaurantes;
```

Debes ver:
- `modelo_representacion_firmado` (tinyint/boolean)
- `modelo_representacion_archivo` (varchar)
- `modelo_representacion_fecha` (timestamp)
- `modelo_representacion_observaciones` (text)

---

## 🐛 Solución de Problemas

### Error: "Missing invoice_id" en webhook

**Causa:** El payload del webhook no incluye `invoice_id`

**Solución:** Verifica que VeriFactu esté enviando el campo correcto. Revisa los logs:
```bash
tail -f storage/logs/laravel.log | grep "VeriFactu Webhook"
```

### Error: "Invoice not found"

**Causa:** El `verifactu_id` no existe en tu base de datos

**Solución:**
1. Verifica que la factura se envió correctamente
2. Comprueba: `SELECT verifactu_id FROM facturas WHERE id = X;`

### Error: "La factura no ha sido enviada a VeriFactu"

**Causa:** Intentas verificar estado de factura en estado `borrador` o `emitida`

**Solución:** Solo funciona con facturas en estado `enviada`

### No aparece la sección de Credenciales VeriFactu API

**Causa:** La vista `fiscal-config.blade.php` no se actualizó correctamente

**Solución:** Verifica que el archivo contiene la sección de credenciales

---

## ✅ Checklist de Pruebas

### Pruebas Básicas (sin VeriFactu):
- [ ] Migración ejecutada correctamente
- [ ] Campos nuevos visibles en base de datos
- [ ] Formulario de Credenciales API visible en UI
- [ ] Guardar credenciales funciona
- [ ] Credenciales se encriptan en BD

### Pruebas con VeriFactu (cuenta real):
- [ ] Login en VeriFactu API funciona
- [ ] Generación de factura funciona
- [ ] Emisión de factura funciona
- [ ] Envío a VeriFactu funciona
- [ ] QR se guarda correctamente
- [ ] Webhook recibido y procesado
- [ ] Estado AEAT actualizado correctamente

### Pruebas de Webhook:
- [ ] Webhook simulado con curl funciona
- [ ] Logs muestran payload recibido
- [ ] Factura se marca como aceptada
- [ ] Factura se marca como rechazada (con error)
- [ ] Timestamp de respuesta AEAT correcto

---

## 📊 Queries Útiles para Debugging

### Ver última factura enviada:

```sql
SELECT
    id,
    numero_factura,
    estado,
    verifactu_id,
    SUBSTRING(verifactu_qr_url, 1, 50) as qr_preview,
    aeat_estado,
    fecha_envio_verifactu,
    aeat_fecha_respuesta
FROM facturas
WHERE estado = 'enviada'
ORDER BY id DESC
LIMIT 1;
```

### Ver facturas pendientes de AEAT:

```sql
SELECT
    numero_factura,
    estado,
    aeat_estado,
    TIMESTAMPDIFF(MINUTE, fecha_envio_verifactu, NOW()) as minutos_esperando
FROM facturas
WHERE aeat_estado = 'pendiente'
ORDER BY fecha_envio_verifactu DESC;
```

### Ver facturas rechazadas:

```sql
SELECT
    numero_factura,
    verifactu_error,
    aeat_response
FROM facturas
WHERE aeat_estado = 'rechazada';
```

---

## 🎯 Próximos Pasos Después de Probar

1. **Si todo funciona:**
   - Configura webhook en producción
   - Obtén credenciales reales de VeriFactu
   - Sube certificado digital real
   - Firma y sube Modelo de Representación
   - Emite facturas reales

2. **Añadir QR a PDFs:**
   - Modifica tu template de factura PDF
   - Incluye `<img src="{{ $factura->verifactu_qr_url }}">`

3. **Crear Declaración Responsable:**
   - Descarga plantilla de VeriFactu
   - Publícala en `/declaracion-responsable`

---

**¿Necesitas ayuda?** Revisa `VERIFACTU_IMPLEMENTATION.md` para más detalles técnicos.
