# Implementación VeriFacti - FlexFood

## Resumen de cambios implementados

Este documento detalla todos los cambios realizados para cumplir al 100% con los requisitos de Veri*Factu de la AEAT, utilizando VeriFacti (verifacti.com) como plataforma de gestión de facturación electrónica.

## ⚠️ IMPORTANTE: VeriFacti vs VeriFactu

**FlexFood utiliza VeriFacti** (https://www.verifacti.com/), una plataforma SaaS que simplifica la implementación:

- ✅ **No requiere certificado digital propio** (VeriFacti firma con el suyo)
- ✅ **No requiere Modelo de Representación manual** (VeriFacti lo gestiona)
- ✅ **API más simple** (Bearer token directo, sin login)
- ✅ **Gestión completa** del envío a AEAT y respuestas

---

## ✅ Elementos implementados

### 1. **QR VeriFactu en facturas** (OBLIGATORIO)

#### Cambios en base de datos:
- **Migración:** `2025_11_26_221756_add_qr_and_aeat_fields_to_facturas_table.php`
- **Nuevos campos en tabla `facturas`:**
  - `verifactu_qr_url` - URL del QR generado por VeriFactu
  - `verifactu_qr_data` - Datos del QR (por si se necesita regenerar)
  - `aeat_estado` - Estado de la respuesta AEAT (pendiente/aceptada/rechazada)
  - `aeat_response` - Respuesta completa de la AEAT (JSON)
  - `aeat_fecha_respuesta` - Timestamp de la respuesta

#### Cambios en código:
- **Modelo `Factura`:** Añadidos campos al `$fillable` y `$casts`
- **Nuevos métodos en Factura:**
  - `marcarComoEnviada()` - Actualizado para guardar QR
  - `marcarAceptadaAEAT()` - Marca factura como aceptada por AEAT
  - `marcarRechazadaAEAT()` - Marca factura como rechazada
  - `tieneQR()` - Verifica si tiene QR
  - `aceptadaPorAEAT()`, `rechazadaPorAEAT()`, `pendienteAEAT()` - Helpers de estado

- **InvoiceService:** Actualizado `enviarAVeriFactu()` para extraer y guardar el QR de la respuesta

#### Uso:
El QR se guarda automáticamente cuando se envía una factura a VeriFactu. Debe mostrarse en el PDF/ticket de la factura.

---

### 2. **Webhook para respuestas AEAT** (IMPORTANTE)

#### Nuevo controlador:
- **Archivo:** `app/Http/Controllers/VeriFactuWebhookController.php`
- **Métodos:**
  - `handle()` - Procesa webhooks de VeriFactu con respuestas AEAT
  - `verificarEstado()` - Consulta manual del estado de una factura

#### Rutas añadidas:
```php
// Webhook público (sin CSRF)
POST /webhooks/verifactu -> VeriFactuWebhookController@handle

// Verificación manual (autenticado)
GET /facturas/{factura}/verificar-estado -> VeriFactuWebhookController@verificarEstado
```

#### Configuración requerida:
**Debes configurar esta URL en el panel de VeriFactu:**
```
https://tudominio.com/webhooks/verifactu
```

El webhook recibe notificaciones cuando la AEAT acepta o rechaza una factura y actualiza automáticamente el estado.

---

### 3. **Credenciales VeriFacti API** (NECESARIO)

#### Interfaz de usuario:
- **Vista:** Añadida sección en `resources/views/settings/partials/fiscal-config.blade.php`
- **Formulario para configurar:**
  - Usuario (NIF)
  - API Key (encriptada)
  - Link de registro: https://www.verifacti.com/

#### Nuevo método en controlador:
- **FiscalController::updateCredenciales()** - Guarda credenciales de forma segura

#### Ruta añadida:
```php
POST /fiscal/credenciales -> FiscalController@updateCredenciales
```

#### Métodos en modelo Restaurante (ya existían):
- `tieneCredencialesVeriFactu()` - Verifica si tiene credenciales
- `getVeriFactuApiKeyAttribute()` - Desencripta API key
- `setVeriFactuApiKeyAttribute()` - Encripta API key al guardar

#### VeriFactiService:
- **Archivo:** `app/Services/VeriFactiService.php`
- **Autenticación:** Bearer token (no requiere login)
- **Métodos principales:**
  - `setApiKey()` - Configura API key
  - `healthCheck()` - Verifica estado de la API
  - `crearFactura()` - Crea factura en VeriFacti
  - `consultarEstado()` - Consulta estado de una factura
- **Formato de datos:** DD-MM-YYYY, máximo 12 líneas por factura

---

### 4. **Modelo de Representación** (GESTIONADO POR VERIFACTI)

#### ℹ️ Importante con VeriFacti:
**VeriFacti gestiona automáticamente el Modelo de Representación.** No es necesario que el restaurante lo descargue, firme y suba manualmente.

VeriFacti actúa como representante del restaurante ante la AEAT, por lo que ellos manejan toda la documentación legal requerida.

#### Campos en base de datos (mantienen para compatibilidad):
- **Migración:** `2025_11_26_222728_add_modelo_representacion_to_restaurantes_table.php`
- **Nuevos campos en tabla `restaurantes`:**
  - `modelo_representacion_firmado` - Boolean, indica si está firmado
  - `modelo_representacion_archivo` - Ruta al PDF firmado
  - `modelo_representacion_fecha` - Timestamp de subida
  - `modelo_representacion_observaciones` - Notas adicionales

**Nota:** Estos campos se mantienen para futura compatibilidad si un restaurante desea usar su propio certificado en lugar del de VeriFacti

---

## 🔧 Pendiente de completar (Opcional)

### 1. **Declaración Responsable** (Opcional)
VeriFacti gestiona la declaración responsable, pero si deseas publicarla en tu sitio:
- Crear vista `resources/views/declaracion-responsable.blade.php`
- Añadir ruta pública `GET /declaracion-responsable`
- Enlazar desde el footer de la aplicación

### 2. **Testing en producción**
- Obtener credenciales reales de VeriFacti
- Probar envío de facturas reales
- Verificar recepción de webhooks de AEAT
- Validar QR codes generados

---

## 📋 Checklist de cumplimiento Veri*Factu

| Requisito | Estado | Implementación |
|-----------|--------|----------------|
| ✅ Datos fiscales del restaurante | Completo | Ya existía |
| ✅ Series de facturación | Completo | Ya existía |
| ✅ Generación de facturas | Completo | Ya existía |
| ✅ **Integración VeriFacti API** | **Implementado** | **VeriFactiService** |
| ✅ **QR Veri*Factu en factura** | **Implementado** | **Nuevo** |
| ✅ **Webhook respuesta AEAT** | **Implementado** | **Nuevo** |
| ✅ **Credenciales API (UI)** | **Implementado** | **Nuevo** |
| ✅ **Certificado digital** | **Opcional** | **VeriFacti lo gestiona** |
| ✅ **Modelo Representación** | **Gestionado** | **VeriFacti lo gestiona** |
| ⚠️ **Declaración Responsable** | **Opcional** | **VeriFacti lo gestiona** |

---

## 🚀 Pasos para finalizar la implementación

### 1. Ejecutar migraciones (si no se ha hecho)
```bash
php artisan migrate
```

Esto creará los nuevos campos en las tablas `facturas` y `restaurantes`.

### 2. Registrarse en VeriFacti
- Accede a https://www.verifacti.com/
- Crea una cuenta
- Activa modo TEST para pruebas
- Obtén tus credenciales (Usuario NIF + API Key)

### 3. Configurar webhook en VeriFacti
- Accede a tu panel de VeriFacti
- Configuración → Webhooks
- Añade: `https://tudominio.com/webhooks/verifactu`

### 4. Configurar en FlexFood
- Accede a `/r/{restaurante-slug}/settings` → Fiscal
- Completa datos fiscales
- Configura credenciales de VeriFacti API
- Crea serie de facturación
- Habilita facturación

### 5. Probar integración
```bash
php artisan verifacti:test
```

---

## 📝 Configuración del restaurante

### Orden de configuración recomendado:

1. **Datos fiscales básicos**
   - Razón social, NIF, dirección, régimen IVA

2. **Credenciales VeriFacti API**
   - Usuario (NIF)
   - API Key (obtener de https://www.verifacti.com/)

3. **Series de facturación**
   - Crear serie principal (ej: "FF", "2025", etc.)

4. **Habilitar facturación**
   - Una vez completados los pasos anteriores

### ℹ️ Con VeriFacti NO necesitas:
- ❌ Certificado digital propio (VeriFacti usa el suyo)
- ❌ Modelo de Representación manual (VeriFacti lo gestiona)
- ❌ Declaración Responsable manual (VeriFacti lo gestiona)

---

## 🔒 Seguridad

- **API Keys encriptadas:** Se guardan con `Crypt::encryptString()`
- **Certificados en storage privado:** `storage/app/certificados/{restaurante_id}/`
- **Modelos en storage privado:** `storage/app/modelos_representacion/{restaurante_id}/`
- **Webhook sin CSRF:** Necesario para recibir callbacks de VeriFactu
- **Logs completos:** Todos los webhooks se registran en Laravel logs

---

## 📊 Flujo completo de facturación

```
1. Restaurante configura todo
   ├─ Datos fiscales
   ├─ Credenciales VeriFacti API
   └─ Serie de facturación

2. Se genera factura desde orden
   ├─ InvoiceService::generarFacturaDesdeOrden()
   └─ Estado: borrador

3. Se emite factura
   ├─ InvoiceService::emitirFactura()
   └─ Estado: emitida

4. Se envía a VeriFacti
   ├─ InvoiceService::enviarAVeriFactu()
   ├─ VeriFactiService::setApiKey() ← Bearer token
   ├─ VeriFactiService::crearFactura() ← POST /verifactu/create
   ├─ Recibe uuid, qr_base64, qr_url, huella ← NUEVO
   ├─ Guarda QR en factura ← NUEVO
   └─ Estado: enviada, AEAT: pendiente ← NUEVO

5. VeriFacti envía a AEAT
   └─ Proceso asíncrono (puede tardar minutos)

6. AEAT responde
   ├─ VeriFacti recibe respuesta
   └─ VeriFacti llama webhook de FlexFood ← NUEVO

7. FlexFood procesa webhook ← NUEVO
   ├─ VeriFactuWebhookController::handle()
   ├─ Si aceptada → AEAT: aceptada
   └─ Si rechazada → AEAT: rechazada + error

8. Factura lista
   └─ Estado: enviada, AEAT: aceptada ✓
```

---

## 🛠️ Archivos modificados/creados

### Migraciones:
- `2025_11_26_221756_add_qr_and_aeat_fields_to_facturas_table.php`
- `2025_11_26_222728_add_modelo_representacion_to_restaurantes_table.php`

### Modelos:
- `app/Models/Factura.php` - Añadidos campos y métodos
- `app/Models/Restaurante.php` - Añadidos campos y métodos

### Controladores:
- `app/Http/Controllers/FiscalController.php` - Añadidos métodos
- `app/Http/Controllers/VeriFactuWebhookController.php` - **NUEVO**

### Servicios:
- `app/Services/VeriFactiService.php` - **NUEVO** - Integración con VeriFacti API
- `app/Services/InvoiceService.php` - Actualizado para usar VeriFacti

### Comandos:
- `app/Console/Commands/TestVeriFacti.php` - **NUEVO** - Comando de prueba

### Vistas:
- `resources/views/settings/partials/fiscal-config.blade.php` - Sección credenciales API

### Rutas:
- `routes/web.php` - Añadidas rutas de webhooks y credenciales

---

## ❓ Preguntas frecuentes

### ¿Dónde se imprime el QR en la factura?
El QR se guarda en `factura->verifactu_qr_url`. Debes añadirlo al template PDF/ticket de las facturas.

### ¿Qué pasa si no recibo el webhook?
Puedes consultar manualmente: `GET /facturas/{id}/verificar-estado`

### ¿Necesito certificado digital?
No con VeriFacti. VeriFacti firma las facturas con su propio certificado. Solo lo necesitas si quieres firmar con el tuyo propio.

### ¿El Modelo de Representación se gestiona manualmente?
No con VeriFacti. VeriFacti actúa como tu representante y gestiona toda la documentación legal.

### ¿Puedo probar sin credenciales reales?
Sí, VeriFacti tiene modo test (`VERIFACTU_TEST_MODE=true` en `.env`).

### ¿Cómo pruebo la integración?
Ejecuta: `php artisan verifacti:test`

---

## 📞 Soporte

- **VeriFacti:** https://www.verifacti.com/
- **Panel VeriFacti:** (URL proporcionada tras registro)
- **Normativa AEAT:** Veri*Factu - Reglamento de facturación electrónica
- **Testing:** `php artisan verifacti:test`

---

*Última actualización: 26/11/2025 - Migrado a VeriFacti*
