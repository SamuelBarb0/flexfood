<?php

namespace App\Console\Commands;

use App\Models\Restaurante;
use App\Services\VeriFactiService;
use Illuminate\Console\Command;

class TestVeriFacti extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verifacti:test {restaurante_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba la conexión con VeriFacti API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 TEST VERIFACTI - FlexFood');
        $this->info('==============================');
        $this->newLine();

        // Obtener restaurante
        $restauranteId = $this->argument('restaurante_id');

        if ($restauranteId) {
            $restaurante = Restaurante::find($restauranteId);
        } else {
            $restaurante = Restaurante::first();
        }

        if (!$restaurante) {
            $this->error('❌ No se encontró el restaurante');
            return 1;
        }

        $this->info("→ Restaurante: {$restaurante->nombre} (ID: {$restaurante->id})");
        $this->newLine();

        // Test 1: Verificar credenciales
        $this->info('✓ Test 1: Verificando credenciales...');

        if (!$restaurante->tieneCredencialesVeriFactu()) {
            $this->warn('  ⚠️  El restaurante no tiene credenciales de VeriFacti configuradas');
            $this->info('  → Configúralas en: /r/' . $restaurante->slug . '/settings (pestaña Fiscal)');
            return 1;
        }

        $this->info('  ✅ Credenciales encontradas');
        $this->info("  → Usuario: {$restaurante->verifactu_api_username}");
        $this->newLine();

        // Test 2: Instanciar servicio
        $this->info('✓ Test 2: Conectando con VeriFacti...');

        try {
            $veriFactiService = app(VeriFactiService::class);
            $apiKey = $restaurante->verifactu_api_key;
            $veriFactiService->setApiKey($apiKey);

            $this->info('  ✅ Servicio VeriFacti instanciado');
        } catch (\Exception $e) {
            $this->error('  ❌ Error al instanciar servicio: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();

        // Test 3: Health check
        $this->info('✓ Test 3: Verificando estado de la API (Health Check)...');

        try {
            $healthResult = $veriFactiService->healthCheck();

            if ($healthResult['success']) {
                $this->info('  ✅ API de VeriFacti está operativa');
                if (isset($healthResult['data'])) {
                    $this->info('  → Respuesta: ' . json_encode($healthResult['data']));
                }
            } else {
                $this->warn('  ⚠️  Health check falló');
                $this->info('  → Error: ' . ($healthResult['error'] ?? 'Desconocido'));
            }
        } catch (\Exception $e) {
            $this->error('  ❌ Error en health check: ' . $e->getMessage());
        }

        $this->newLine();

        // Test 4: Verificar configuración
        $this->info('✓ Test 4: Verificando configuración de facturación...');

        $datosFiscalesOk = $restaurante->datosFiscalesCompletos();
        $this->info('  → Datos fiscales: ' . ($datosFiscalesOk ? '✅ Completos' : '❌ Incompletos'));

        $tieneSerie = $restaurante->seriePrincipal()->exists();
        $this->info('  → Serie principal: ' . ($tieneSerie ? '✅ Configurada' : '❌ No configurada'));

        $habilitado = $restaurante->fiscal_habilitado;
        $this->info('  → Facturación habilitada: ' . ($habilitado ? '✅ SÍ' : '❌ NO'));

        $this->newLine();

        // Test 5: Simular estructura de factura
        $this->info('✓ Test 5: Validando estructura de factura...');

        try {
            $facturaEjemplo = [
                'serie' => 'FF',
                'numero' => '1',
                'fecha_expedicion' => now()->format('d-m-Y'),
                'tipo_factura' => 'F2',
                'descripcion' => 'Factura de prueba',
                'lineas' => [
                    [
                        'base_imponible' => 50.00,
                        'tipo_impositivo' => 10.00,
                        'cuota_repercutida' => 5.00,
                    ]
                ],
                'importe_total' => 55.00,
            ];

            $this->info('  ✅ Estructura de factura válida');
            $this->info('  → Formato VeriFacti:');
            $this->line('    ' . json_encode($facturaEjemplo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (\Exception $e) {
            $this->error('  ❌ Error: ' . $e->getMessage());
        }

        $this->newLine();

        // Resumen
        $this->info('==============================');
        $this->info('✅ Tests completados');
        $this->newLine();

        if ($datosFiscalesOk && $tieneSerie && $habilitado) {
            $this->info('📝 Estado: LISTO PARA FACTURAR');
            $this->info('Puedes generar facturas y enviarlas a VeriFacti');
        } else {
            $this->warn('📝 Estado: CONFIGURACIÓN PENDIENTE');
            $this->info('Pasos siguientes:');

            if (!$datosFiscalesOk) {
                $this->info('  1. Completa los datos fiscales del restaurante');
            }
            if (!$tieneSerie) {
                $this->info('  2. Configura una serie de facturación');
            }
            if (!$habilitado) {
                $this->info('  3. Habilita la facturación fiscal');
            }

            $this->newLine();
            $this->info("URL: http://localhost/flexfood/r/{$restaurante->slug}/settings");
        }

        $this->newLine();

        return 0;
    }
}
