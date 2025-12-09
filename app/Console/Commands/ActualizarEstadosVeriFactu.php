<?php

namespace App\Console\Commands;

use App\Models\Factura;
use App\Models\Restaurante;
use App\Services\VeriFactiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ActualizarEstadosVeriFactu extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verifactu:actualizar-estados';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza los estados de AEAT para facturas enviadas a VeriFactu';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Actualizando estados de VeriFactu...');

        // Obtener facturas enviadas que están pendientes de confirmación de AEAT
        $facturas = Factura::where('estado', 'enviada')
            ->where('aeat_estado', 'pendiente')
            ->whereNotNull('verifactu_id')
            ->with('restaurante')
            ->get();

        if ($facturas->isEmpty()) {
            $this->info('✅ No hay facturas pendientes de actualizar.');
            return 0;
        }

        $this->info("📋 Encontradas {$facturas->count()} facturas pendientes.");

        $actualizadas = 0;
        $errores = 0;

        foreach ($facturas as $factura) {
            try {
                $restaurante = $factura->restaurante;

                // Verificar que el restaurante tenga credenciales
                if (!$restaurante->tieneCredencialesVeriFactu()) {
                    $this->warn("⚠️  Factura {$factura->numero_factura}: Restaurante sin credenciales");
                    continue;
                }

                // Consultar estado en VeriFacti
                $veriFactiService = app(VeriFactiService::class);
                $veriFactiService->setApiKey($restaurante->verifactu_api_key);

                $resultado = $veriFactiService->consultarEstado($factura->verifactu_id);

                if ($resultado['success']) {
                    $data = $resultado['data'];

                    // Actualizar QR si está disponible y no lo teníamos antes
                    if (empty($factura->verifactu_qr_data) && !empty($data['qr'])) {
                        $factura->verifactu_qr_data = $data['qr'];
                        $this->comment("📱 QR actualizado para factura {$factura->numero_factura}");
                    }

                    // Actualizar URL de QR si está disponible
                    if (empty($factura->verifactu_qr_url) && !empty($data['enlace_verificacion'])) {
                        $factura->verifactu_qr_url = $data['enlace_verificacion'];
                    }

                    // Actualizar huella si no la teníamos
                    if (empty($factura->verifactu_huella) && !empty($data['huella'])) {
                        $factura->verifactu_huella = $data['huella'];
                        $this->comment("🔐 Huella actualizada para factura {$factura->numero_factura}");
                    }

                    // Actualizar estado de AEAT si está disponible
                    if (isset($data['estado_aeat'])) {
                        $estadoAeat = $data['estado_aeat'];

                        // Mapear estados de VeriFacti a nuestro sistema
                        if (in_array($estadoAeat, ['Aceptada', 'Registrada'])) {
                            $factura->aeat_estado = 'aceptada';
                        } elseif (in_array($estadoAeat, ['Rechazada', 'AceptadaConErrores'])) {
                            $factura->aeat_estado = 'rechazada';
                        }

                        $factura->aeat_response = $data;
                        $factura->aeat_fecha_respuesta = now();
                        $factura->save();

                        $actualizadas++;
                        $this->info("✅ Factura {$factura->numero_factura}: {$estadoAeat}");
                    } else {
                        // Aunque no haya estado de AEAT, guardar los cambios si actualizamos QR o huella
                        $factura->save();
                        $this->comment("⏳ Factura {$factura->numero_factura}: Aún pendiente en AEAT");
                    }
                } else {
                    $errores++;
                    $this->error("❌ Factura {$factura->numero_factura}: {$resultado['error']}");
                }

            } catch (\Exception $e) {
                $errores++;
                $this->error("❌ Error al actualizar factura {$factura->numero_factura}: {$e->getMessage()}");
                Log::error('Error al actualizar estado VeriFactu', [
                    'factura_id' => $factura->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("📊 Resumen:");
        $this->info("   ✅ Actualizadas: {$actualizadas}");
        $this->info("   ❌ Errores: {$errores}");
        $this->info("   ⏳ Pendientes: " . ($facturas->count() - $actualizadas - $errores));

        return 0;
    }
}
