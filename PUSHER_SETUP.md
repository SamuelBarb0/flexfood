# Configuración de Pusher para FlexFood

## ✅ Completado

Se ha implementado Pusher para notificaciones en tiempo real en las comandas y dashboard. Ahora el sistema usa WebSockets en lugar de polling cada 6 segundos.

## 📋 Pasos para Activar

### 1. Crear una cuenta en Pusher

1. Ve a [pusher.com](https://pusher.com) y crea una cuenta gratuita
2. Crea una nueva app (Channels)
3. Selecciona el cluster más cercano (recomendado: `eu` para Europa o `us2` para USA)
4. Copia las credenciales: App ID, Key, Secret y Cluster

### 2. Configurar las variables de entorno

Edita tu archivo `.env` y agrega/actualiza estas líneas:

```env
BROADCAST_CONNECTION=pusher

PUSHER_APP_ID=tu-app-id-aqui
PUSHER_APP_KEY=tu-key-aqui
PUSHER_APP_SECRET=tu-secret-aqui
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

**Importante**: Reemplaza `tu-app-id-aqui`, `tu-key-aqui`, `tu-secret-aqui` y `mt1` con tus credenciales reales de Pusher.

### 3. Instalar dependencias de JavaScript

Las dependencias ya están instaladas, pero si necesitas reinstalarlas:

```bash
npm install
```

### 4. Compilar assets

Compila los assets para que Vite incluya la configuración de Pusher:

```bash
npm run build
```

Para desarrollo con hot reload:

```bash
npm run dev
```

### 5. Iniciar el queue worker

**CRÍTICO**: Para que los eventos se transmitan a Pusher, el queue worker debe estar ejecutándose:

```bash
php artisan queue:listen --tries=1
```

O usando el comando de desarrollo completo:

```bash
composer dev
```

Este comando inicia automáticamente: servidor, queue worker, logs y vite.

## 🔍 Verificar que funciona

### Prueba 1: Consola del navegador

1. Abre el dashboard o comandas en tu navegador
2. Abre la consola de desarrollador (F12)
3. Deberías ver: `✅ Pusher configurado para canal: restaurante.tu-slug`

### Prueba 2: Panel de Pusher

1. Ve a tu dashboard de Pusher en pusher.com
2. Selecciona tu app
3. Ve a la pestaña "Debug Console"
4. Activa una orden desde el panel de comandas
5. Deberías ver el evento `orden.cambio` aparecer en tiempo real

### Prueba 3: Múltiples pestañas

1. Abre el dashboard en dos pestañas diferentes
2. Cambia el estado de una orden en una pestaña
3. La otra pestaña debería actualizarse instantáneamente sin recargar

## 🔄 Fallback automático

Si Pusher no está configurado o falla:
- El sistema automáticamente vuelve al polling cada 6 segundos
- Verás en consola: `⚠️ Echo no está disponible, usando polling como fallback`
- Todo seguirá funcionando, pero sin actualizaciones en tiempo real

## 🏗️ Arquitectura implementada

### Backend (Laravel)
- **Evento**: `App\Events\OrderStatusChanged`
- **Canal**: `restaurante.{slug}` (público, aislado por tenant)
- **Acciones que disparan eventos**:
  - `crear` - Nueva orden creada
  - `activar` - Orden activada (estado 0 → 1)
  - `entregar` - Orden entregada completa (estado → 2)
  - `entregar_parcial` - Productos entregados parcialmente
  - `cancelar` - Orden cancelada/archivada
  - `finalizar` - Mesa cerrada (estado → 4)

### Frontend (JavaScript)
- **Laravel Echo** configurado en `resources/js/bootstrap.js`
- **Listeners** en:
  - `resources/views/comandas/index.blade.php` (panel de cocina)
  - `resources/views/dashboard.blade.php` (TPV/punto de venta)

### Flujo de eventos
```
Usuario hace cambio → OrdenController
    ↓
broadcast(OrderStatusChanged)
    ↓
Queue Worker procesa el job
    ↓
Pusher API (WebSocket)
    ↓
Todos los clientes conectados reciben notificación
    ↓
refrescarPanel() actualiza la UI
```

## 🐛 Troubleshooting

### No veo eventos en Pusher Debug Console

**Problema**: El queue worker no está corriendo
**Solución**: Ejecuta `php artisan queue:listen --tries=1`

### Error "Class 'Pusher\Pusher' not found"

**Problema**: Falta la dependencia PHP de Pusher
**Solución**: Ejecuta `composer install`

### Frontend no se conecta a Pusher

**Problema**: Assets no compilados con las nuevas variables de entorno
**Solución**:
1. Detén `npm run dev` si está corriendo
2. Ejecuta `npm run build`
3. Recarga la página con Ctrl+F5

### Events no se disparan

**Problema**: Las variables VITE_PUSHER_* no están definidas
**Solución**:
1. Verifica que estén en `.env`
2. Reinicia `npm run dev`
3. Los cambios en `.env` requieren reiniciar Vite

## 📊 Límites del plan gratuito de Pusher

- **200,000 mensajes/día**
- **100 conexiones concurrentes**
- **Unlimited channels**

Para un restaurante, esto es más que suficiente. Cada cambio de estado de orden = 1 mensaje.

## 🎯 Beneficios obtenidos

✅ **Actualizaciones instantáneas** - Sin esperar 6 segundos
✅ **Menos carga en el servidor** - No más polling constante
✅ **Mejor UX** - Los cambios aparecen inmediatamente
✅ **Multi-usuario** - Varios dispositivos sincronizados en tiempo real
✅ **Aislamiento por tenant** - Cada restaurante tiene su propio canal

## 📝 Notas adicionales

- El sistema mantiene compatibilidad con polling como fallback
- Los canales son públicos pero aislados por slug de restaurante
- Los eventos usan `->toOthers()` para no duplicar updates en quien hizo el cambio
- El broadcasting es asíncrono vía queues para no ralentizar las respuestas HTTP

---

**¿Preguntas?** Revisa la documentación de Pusher: https://pusher.com/docs/channels/getting_started/laravel
