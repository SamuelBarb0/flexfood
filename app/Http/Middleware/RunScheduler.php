<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RunScheduler
{
    /**
     * Handle an incoming request.
     *
     * Ejecuta el scheduler automáticamente cada ~50 segundos cuando alguien
     * visita el sitio. Esto elimina la necesidad de un cron job.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo ejecutar si han pasado al menos 50 segundos desde la última ejecución
        $lastRun = Cache::get('scheduler_last_run', 0);
        $now = time();

        if ($now - $lastRun >= 50) {
            // Ejecutar scheduler en background para no bloquear la petición
            if (function_exists('exec') && !str_contains(ini_get('disable_functions'), 'exec')) {
                // Método 1: Ejecutar en background con exec (no bloquea)
                $command = 'cd ' . base_path() . ' && php artisan schedule:run > /dev/null 2>&1 &';
                @exec($command);
            } else {
                // Método 2: Ejecutar directamente (puede ser lento pero funciona siempre)
                Artisan::call('schedule:run');
            }

            // Actualizar timestamp
            Cache::put('scheduler_last_run', $now, 120);
        }

        return $next($request);
    }
}
