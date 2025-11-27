<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->onDelete('cascade');
            $table->foreignId('serie_facturacion_id')->constrained('series_facturacion')->onDelete('restrict');
            $table->foreignId('orden_id')->nullable()->constrained('ordenes')->onDelete('set null');
            $table->foreignId('comercio_fiscal_id')->nullable()->constrained('comercios_fiscales')->onDelete('set null');

            // Numeración y identificación
            $table->string('numero_factura', 50)->unique(); // Formato completo: FF-000001-2025
            $table->string('numero_serie', 50); // Solo el número: 000001
            $table->date('fecha_emision');
            $table->date('fecha_operacion')->nullable();

            // Tipo de factura según VeriFactu
            // F1: Factura (expedida por el obligado tributario)
            // F2: Factura Simplificada
            // F3: Factura emitida en sustitución de facturas simplificadas facturadas y declaradas
            // R1-R5: Facturas rectificativas
            $table->enum('tipo_factura', ['F1', 'F2', 'F3', 'R1', 'R2', 'R3', 'R4', 'R5'])->default('F1');

            // Referencia a factura rectificada (si es rectificativa)
            $table->foreignId('factura_rectificada_id')->nullable()->constrained('facturas')->onDelete('set null');
            $table->text('motivo_rectificacion')->nullable();

            // Totales calculados
            $table->decimal('base_imponible', 10, 2)->default(0);
            $table->decimal('total_iva', 10, 2)->default(0);
            $table->decimal('total_recargo', 10, 2)->default(0); // Recargo de equivalencia
            $table->decimal('total_factura', 10, 2)->default(0);

            // Desglose de IVA (JSON para múltiples tipos)
            // Estructura: [{"tipo_iva": 10, "base": 100.00, "cuota": 10.00}, ...]
            $table->json('desglose_iva')->nullable();

            // Descripción general
            $table->text('descripcion')->nullable();
            $table->text('observaciones')->nullable();

            // Estado y control
            $table->enum('estado', ['borrador', 'emitida', 'enviada', 'anulada'])->default('borrador');
            $table->timestamp('fecha_envio_verifactu')->nullable();
            $table->string('verifactu_id')->nullable(); // ID devuelto por VeriFactu
            $table->text('verifactu_response')->nullable(); // Respuesta JSON de VeriFactu
            $table->text('verifactu_error')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['restaurante_id', 'estado']);
            $table->index(['restaurante_id', 'fecha_emision']);
            $table->index(['serie_facturacion_id', 'numero_serie']);
            $table->index('orden_id');
            $table->index('verifactu_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
