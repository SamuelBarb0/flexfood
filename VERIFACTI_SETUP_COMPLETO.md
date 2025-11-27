# ✅ CONFIGURACIÓN VERIFACTI COMPLETADA

**Fecha**: 27/11/2025
**Sistema**: FlexFood 3.7.3.6nd
**Servicio**: VeriFacti (https://www.verifacti.com/)

---

## 📋 RESUMEN DE LA IMPLEMENTACIÓN

Se ha implementado **facturación automática** integrada con **VeriFacti** para cumplir con la normativa española de Veri*Factu.

### ✅ Funcionalidades Implementadas

1. **Generación Automática de Facturas**
   - Al finalizar un pedido (estado = 4), se genera automáticamente una factura
   - Se emite y envía a VeriFacti sin intervención manual
   - Incluye código QR de verificación de AEAT

2. **Gestión de Credenciales**
   - Almacenamiento seguro de API Key encriptada
   - Configuración por restaurante desde `/r/{slug}/settings` → Pestaña Fiscal

3. **Integración con VeriFacti**
   - Conexión directa con API de VeriFacti
   - Envío automático al finalizar pedidos
   - Recepción de UUID y QR code

---

## 🔧 CONFIGURACIÓN NECESARIA

### 1. Variables de Entorno (.env)

```env
VERIFACTU_API_URL=https://api.verifacti.com
VERIFACTU_TEST_MODE=true
VERIFACTU_TIMEOUT=30
```

### 2. Credenciales del Restaurante

Ir a: **Configuración → Fiscal → Credenciales VeriFacti API**

- **Usuario (NIF)**: B75777847
- **API Key**: `vf_test_SPNippsi6kE4xIeDpF+5l1AD8CcL8oJ7TiQSRXD2fqU=`

✅ Estado: **Configuradas correctamente**

### 3. Facturación Automática

**Toggle**: Habilitado ✅

Cuando está activado, al finalizar un pedido se genera automáticamente:
1. Factura en base de datos
2. Emisión de la factura
3. Envío a VeriFacti
4. Almacenamiento de UUID y QR

---

## 📊 FLUJO AUTOMÁTICO

```
Usuario finaliza pedido
         ↓
Estado cambia a 4 (Finalizada)
         ↓
Sistema verifica:
  ✓ fiscal_habilitado = true
  ✓ facturacion_automatica = true
         ↓
Genera factura desde orden
  - Productos con IVA 10%
  - Serie: FF-2025
  - Número correlativo
         ↓
Emite factura (estado: emitida)
         ↓
Envía a VeriFacti API
  POST https://api.verifacti.com/verifactu/create
         ↓
VeriFacti responde:
  - UUID
  - QR code (base64)
  - URL de verificación AEAT
  - Huella digital
         ↓
Guarda en BD:
  - verifactu_id (UUID)
  - verifactu_qr_url
  - verifactu_qr_data
  - aeat_estado: "pendiente"
         ↓
✅ Factura visible en panel VeriFacti
```

---

## 🗄️ CAMPOS DE BASE DE DATOS

### Tabla `restaurantes`
- `verifactu_api_username` → NIF del restaurante
- `verifactu_api_key_encrypted` → API Key encriptada
- `fiscal_habilitado` → true/false
- `facturacion_automatica` → true/false

### Tabla `facturas`
- `verifactu_id` → UUID de VeriFacti
- `verifactu_qr_url` → URL del QR de verificación
- `verifactu_qr_data` → Imagen QR en base64
- `aeat_estado` → pendiente/aceptada/rechazada

---

## 🧪 PRUEBAS REALIZADAS

### ✅ Test de Conexión
```bash
php test_verifacti_conexion.php
```

**Resultado**: Conexión exitosa con VeriFacti

### ✅ Test de Envío de Factura
```bash
php test_envio_verifacti.php
```

**Resultado**: Factura enviada correctamente
- UUID: `9fd8e5dc-37d5-4541-865e-6aafab0be762`
- Estado: Pendiente
- QR generado

### ✅ Test de Facturación Automática
```bash
php test_facturacion.php
```

**Resultado**: Factura generada, emitida y enviada automáticamente

---

## 🔍 ENDPOINTS DE VERIFACTI

### Correcto ✅
- **Base URL**: `https://api.verifacti.com`
- **Health Check**: `GET /verifactu/health`
- **Crear Factura**: `POST /verifactu/create`

### Incorrecto ❌
- ~~`https://app.verifactuapi.es`~~ (era la URL anterior)

---

## 📝 ARCHIVOS MODIFICADOS

### Modelos
- `app/Models/Restaurante.php` → Agregado `verifactu_api_key` a fillable
- `app/Models/Factura.php` → Métodos de gestión de estado AEAT
- `app/Models/FacturaLinea.php` → Corrección para usar `precio_base`

### Controladores
- `app/Http/Controllers/OrdenController.php` → Facturación automática al finalizar
- `app/Http/Controllers/FiscalController.php` → Gestión de credenciales

### Servicios
- `app/Services/VeriFactiService.php` → Integración con API VeriFacti
- `app/Services/InvoiceService.php` → Generación y emisión de facturas

### Configuración
- `config/verifactu.php` → Configuración de VeriFacti
- `.env` → URL de API corregida

### Migraciones
- `2025_11_24_214805_add_verifactu_api_credentials_to_restaurantes_table.php`
- `2025_11_26_221756_add_qr_and_aeat_fields_to_facturas_table.php`
- `2025_11_26_235900_add_facturacion_automatica_to_restaurantes_table.php`

---

## 🎯 PRÓXIMOS PASOS

### Para Producción

1. **Cambiar a entorno de producción**:
   ```env
   VERIFACTU_TEST_MODE=false
   ```

2. **Obtener credenciales reales**:
   - Registrarse en https://www.verifacti.com/
   - Obtener API Key de producción
   - Configurar en `/r/{slug}/settings`

3. **Verificar facturas**:
   - Entrar a https://www.verifacti.com/
   - Ver facturas enviadas
   - Verificar estado AEAT

---

## 🐛 RESOLUCIÓN DE PROBLEMAS

### Problema: "Credenciales no detectadas"
**Solución**: Ejecutar migración
```bash
php artisan migrate
```

### Problema: "Error 405 Not Allowed"
**Solución**: Verificar URL en `.env`
```env
VERIFACTU_API_URL=https://api.verifacti.com
```

### Problema: "Factura con total €0.00"
**Solución**: Ya corregido. El modelo ahora usa `precio_base` correctamente.

### Problema: "Solo se pueden emitir facturas en estado borrador"
**Solución**: Ya corregido. Se agregó `refresh()` después de guardar.

---

## 📞 SOPORTE

- **VeriFacti**: https://www.verifacti.com/soporte
- **Documentación API**: https://www.verifacti.com/desarrolladores
- **Ejemplos**: https://www.verifacti.com/desarrolladores/ejemplos

---

## ✅ CHECKLIST FINAL

- [x] Migraciones ejecutadas
- [x] Credenciales configuradas
- [x] URL de API correcta
- [x] Facturación automática habilitada
- [x] Test de conexión exitoso
- [x] Test de envío exitoso
- [x] Integración en flujo de pedidos
- [x] Logging implementado
- [x] Manejo de errores
- [ ] Cambiar a producción (pendiente)

---

**Estado**: ✅ **IMPLEMENTACIÓN COMPLETA Y FUNCIONAL**

*Última actualización: 27/11/2025*
