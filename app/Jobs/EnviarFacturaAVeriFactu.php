<?php

namespace App\Jobs;

use App\Models\Factura;
use App\Services\InvoiceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class EnviarFacturaAVeriFactu implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Factura $factura
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Job VeriFactu iniciado', [
                'factura_id' => $this->factura->id,
                'numero_factura' => $this->factura->numero_factura,
            ]);

            $invoiceService = app(InvoiceService::class);
            $resultado = $invoiceService->enviarAVeriFactu($this->factura);

            // Refrescar factura para obtener QR y datos actualizados
            $this->factura->refresh();

            Log::info('Job VeriFactu completado exitosamente', [
                'factura_id' => $this->factura->id,
                'uuid' => $this->factura->verifactu_id,
                'qr_disponible' => !empty($this->factura->verifactu_qr_url),
            ]);
        } catch (\Exception $e) {
            Log::error('Error en Job VeriFactu', [
                'factura_id' => $this->factura->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Registrar el error en la factura
            $this->factura->registrarErrorVeriFactu($e->getMessage());

            // Re-lanzar excepción para que Laravel marque el job como fallido
            throw $e;
        }
    }

    /**
     * El número de veces que se puede intentar el job.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * El número de segundos de espera antes de reintentar.
     *
     * @var int
     */
    public $backoff = 5;
}
