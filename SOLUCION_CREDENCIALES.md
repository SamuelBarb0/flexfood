# 🔧 Solución: Credenciales No Detectadas

## Problema

Las credenciales de VeriFacti aparecen en la interfaz pero el sistema dice que no existen.

```
✓ Restaurante: ADMIN PRINCIPAL
  → Facturación habilitada: SÍ
  → Facturación automática: SÍ
  → Tiene credenciales VeriFacti: NO  ← PROBLEMA
```

---

## Causa Probable

La migración que agrega los campos `verifactu_api_username` y `verifactu_api_key_encrypted` **no se ha ejecutado**.

---

## ✅ Solución

### PASO 1: Ejecutar migración pendiente

```bash
php artisan migrate
```

**Migración que debe ejecutarse:**
- `2025_11_24_214805_add_verifactu_api_credentials_to_restaurantes_table.php`

Esta migración agrega los siguientes campos a la tabla `restaurantes`:
- `verifactu_api_username` (string)
- `verifactu_api_key_encrypted` (text)
- `verifactu_api_token` (text)
- `verifactu_token_expires_at` (timestamp)

---

### PASO 2: Volver a guardar las credenciales

Después de ejecutar la migración:

1. Ve a `/r/{slug}/settings` → Pestaña **Fiscal**
2. En la sección **"Credenciales VeriFacti API"**:
   - Usuario (NIF): `B75777847`
   - API Key: (tu clave real de VeriFacti)
3. Haz clic en **"Guardar credenciales"**

---

### PASO 3: Verificar que funcionó

Ejecuta de nuevo el script de prueba:

```bash
php test_facturacion.php
```

**Ahora deberías ver:**

```
✓ Restaurante: ADMIN PRINCIPAL
  → Facturación habilitada: SÍ
  → Facturación automática: SÍ
  → Tiene credenciales VeriFacti: SÍ  ✅
```

---

## 🔍 ¿Por qué pasó esto?

### El flujo correcto es:

1. **Guardar credenciales** (FiscalController):
   ```php
   $restaurante->update([
       'verifactu_api_username' => 'B75777847',
       'verifactu_api_key' => 'tu_api_key_real',
   ]);
   ```

2. **El mutador del modelo** encripta automáticamente:
   ```php
   // Restaurante.php - setVeriFactuApiKeyAttribute()
   $this->attributes['verifactu_api_key_encrypted'] = Crypt::encryptString($value);
   ```

3. **Se guarda en la base de datos**:
   - Campo: `verifactu_api_key_encrypted`
   - Valor: `eyJpdiI6IkZxY2...` (texto encriptado)

4. **Al verificar**:
   ```php
   // Restaurante.php - tieneCredencialesVeriFactu()
   return !empty($this->verifactu_api_username) &&
          !empty($this->verifactu_api_key_encrypted);
   ```

### Si la migración NO se ejecutó:

- Los campos `verifactu_api_username` y `verifactu_api_key_encrypted` **no existen** en la tabla
- Laravel **NO genera error** al intentar guardar (por diseño)
- Los datos se pierden silenciosamente
- Al verificar, ambos campos están vacíos → `tieneCredencialesVeriFactu()` retorna `false`

---

## 🧪 Verificación Adicional

### Opción A: Verificar migración ejecutada

```bash
php artisan migrate:status
```

Busca esta línea:
```
✅ 2025_11_24_214805_add_verifactu_api_credentials_to_restaurantes_table
```

Si aparece `Pending`, **no se ejecutó**.

### Opción B: Verificar campos en base de datos

```sql
DESCRIBE restaurantes;
```

Debes ver estas columnas:
- `verifactu_api_username`
- `verifactu_api_key_encrypted`
- `verifactu_api_token`
- `verifactu_token_expires_at`

Si **no aparecen**, ejecuta `php artisan migrate`.

### Opción C: Verificar datos guardados

```sql
SELECT
    id,
    nombre,
    verifactu_api_username,
    CASE
        WHEN verifactu_api_key_encrypted IS NULL THEN 'NULL'
        WHEN verifactu_api_key_encrypted = '' THEN 'EMPTY'
        ELSE CONCAT('EXISTS (length: ', LENGTH(verifactu_api_key_encrypted), ')')
    END as api_key_status
FROM restaurantes
WHERE nombre = 'ADMIN PRINCIPAL';
```

**Resultado esperado después de guardar:**
```
| id | nombre          | verifactu_api_username | api_key_status        |
|----|-----------------|------------------------|-----------------------|
| 1  | ADMIN PRINCIPAL | B75777847              | EXISTS (length: 180)  |
```

---

## 📋 Checklist

- [ ] Ejecutar `php artisan migrate`
- [ ] Verificar que la migración se aplicó con `php artisan migrate:status`
- [ ] Volver a guardar las credenciales desde la UI
- [ ] Ejecutar `php test_facturacion.php`
- [ ] Verificar que aparece "Tiene credenciales VeriFacti: SÍ"

---

## 🎯 Próximo Paso

Una vez que `tieneCredencialesVeriFactu()` retorne `true`, el script podrá:
1. Generar la factura desde la orden
2. Emitirla
3. **Enviarla a VeriFacti** (aquí es donde podría haber otro error si las credenciales son inválidas)

Después de solucionar esto, verifica que las facturas aparezcan en el panel de VeriFacti en:
- https://www.verifacti.com/login

---

*Fecha: 26/11/2025*
