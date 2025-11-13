# Guía de Despliegue en Hostinger - FlexFood

## Pre-requisitos

1. Cuenta de Pusher (ya configurada con tus credenciales actuales)
2. Acceso SSH a Hostinger
3. PHP 8.2+ instalado en el servidor
4. Composer instalado en el servidor

## Paso 1: Preparar Assets Localmente

Antes de subir el proyecto, compila los assets:

```bash
npm run build
```

Esto generará los archivos optimizados en `public/build/`.

## Paso 2: Configurar Variables de Entorno

En tu `.env` de producción en Hostinger, asegúrate de tener:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

BROADCAST_CONNECTION=pusher
QUEUE_CONNECTION=database

PUSHER_APP_ID=2073751
PUSHER_APP_KEY=e604b14f17149b51c354
PUSHER_APP_SECRET=7a8fd3572a1aeff8df64
PUSHER_APP_CLUSTER=us2

VITE_PUSHER_APP_KEY=e604b14f17149b51c354
VITE_PUSHER_APP_CLUSTER=us2
```

**IMPORTANTE:** No uses `"${PUSHER_APP_KEY}"` en producción, pon el valor directo.

## Paso 3: Subir Archivos

Sube estos archivos/carpetas a Hostinger:

```
- app/
- bootstrap/
- config/
- database/
- public/build/          ← IMPORTANTE: Los assets compilados
- resources/
- routes/
- storage/
- vendor/                 ← Ejecuta composer install en servidor
- .env                    ← Con las variables correctas
- composer.json
- package.json
- artisan
```

## Paso 4: Configurar Permisos

Vía SSH, ejecuta:

```bash
cd /home/tu-usuario/public_html
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Paso 5: Ejecutar Comandos de Laravel

```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```

## Paso 6: Configurar Cron Job (CRÍTICO para Pusher)

Para que las notificaciones de Pusher funcionen, necesitas un cron job que procese la cola.

### En el Panel de Hostinger:

1. Ve a **Advanced → Cron Jobs**
2. Haz clic en **Create Cron Job**
3. **Comando:**

```bash
cd /home/USUARIO/public_html && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

4. **Frecuencia:** Cada minuto
   - Minuto: `*`
   - Hora: `*`
   - Día: `*`
   - Mes: `*`
   - Día de la semana: `*`

### ¿Por qué esto es necesario?

El sistema de notificaciones en tiempo real de FlexFood funciona así:

1. Cuando se crea una orden → Laravel dispara un evento
2. El evento se encola en la base de datos (`QUEUE_CONNECTION=database`)
3. El cron job procesa la cola cada minuto
4. El job envía el evento a Pusher
5. Pusher notifica a todos los navegadores conectados
6. Se reproduce el sonido y aparece el badge

**Sin el cron job, los eventos quedan en cola pero nunca se envían a Pusher.**

## Paso 7: Verificar que Funciona

1. Accede a tu dashboard en producción
2. Abre la consola del navegador (F12)
3. Deberías ver estos logs:

```
✅ AudioContext creado, estado: running
📄 DOM ya listo, esperando Alpine...
🚀 Inicializando sistema de notificaciones...
🔍 Alpine disponible: true
🔍 Echo disponible: true
✅ Store de órdenes creado con valor inicial: 0
🎯 Echo detectado, configurando listener...
✅ Suscripción exitosa al canal: restaurante.{slug}
```

4. Crea una orden de prueba desde el menú público
5. **Espera hasta 60 segundos** (tiempo máximo del cron)
6. Deberías escuchar el sonido y ver el badge

## Paso 8: Troubleshooting

### Si no llegan las notificaciones:

1. Verifica que el cron job esté configurado correctamente:
   ```bash
   crontab -l  # Ver crons activos
   ```

2. Verifica que hay jobs en la cola:
   ```bash
   php artisan queue:failed
   ```

3. Procesa la cola manualmente para probar:
   ```bash
   php artisan queue:work --stop-when-empty
   ```

4. Revisa los logs de Pusher en https://dashboard.pusher.com

### Si el badge no aparece:

1. Verifica que los assets estén compilados:
   ```bash
   ls -la public/build/
   ```

2. Limpia caché del navegador (Ctrl+Shift+R)

3. Verifica en consola que Alpine y Echo estén cargados

## Notas Importantes

- **Desarrollo local:** Usa `php artisan queue:listen --tries=1` o `composer dev`
- **Producción (Hostinger):** Usa el cron job con `schedule:run`
- **El cron se ejecuta cada minuto,** por lo que puede haber un delay de hasta 60 segundos
- **Para notificaciones instantáneas,** necesitarías un VPS con procesos background (supervisord)

## Alternativa: VPS en lugar de Hostinger

Si necesitas notificaciones completamente instantáneas (sin delay), considera migrar a:

- DigitalOcean
- AWS Lightsail
- Vultr
- Linode

En un VPS puedes ejecutar `php artisan queue:listen` permanentemente con Supervisor.

## Comandos Útiles

```bash
# Ver logs en vivo
tail -f storage/logs/laravel.log

# Ver cola de trabajos
php artisan queue:monitor

# Limpiar trabajos fallidos
php artisan queue:flush

# Reiniciar todo después de cambios
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## Recursos

- [Documentación de Pusher](https://pusher.com/docs/)
- [Laravel Broadcasting](https://laravel.com/docs/12.x/broadcasting)
- [Laravel Queues](https://laravel.com/docs/12.x/queues)
- [Laravel Scheduling](https://laravel.com/docs/12.x/scheduling)
