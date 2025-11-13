<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class QueueProcessorController extends Controller
{
    /**
     * Procesa la cola de trabajos pendientes.
     * Este endpoint se llama automáticamente desde el frontend cada pocos segundos.
     */
    public function process(Request $request)
    {
        // Verificar que solo se ejecute una vez cada 5 segundos (evitar spam)
        $lastRun = Cache::get('queue_processor_last_run', 0);
        $now = time();

        if ($now - $lastRun < 5) {
            return response()->json([
                'status' => 'skipped',
                'message' => 'Esperando intervalo',
                'next_run_in' => 5 - ($now - $lastRun)
            ]);
        }

        // Ejecutar procesamiento de cola
        try {
            // Procesar hasta 10 jobs de la cola
            Artisan::call('queue:work', [
                '--stop-when-empty' => true,
                '--max-jobs' => 10,
                '--tries' => 1
            ]);

            // Actualizar timestamp
            Cache::put('queue_processor_last_run', $now, 60);

            return response()->json([
                'status' => 'success',
                'message' => 'Cola procesada',
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
