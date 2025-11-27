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
        Schema::create('factura_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained('facturas')->onDelete('cascade');
            $table->foreignId('producto_id')->nullable()->constrained('productos')->onDelete('set null');

            // Orden de la línea en la factura
            $table->integer('orden')->default(0);

            // Descripción del producto/servicio
            $table->string('descripcion');
            $table->text('descripcion_adicional')->nullable();

            // Cantidades y precios
            $table->decimal('cantidad', 10, 2)->default(1);
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2); // cantidad * precio_unitario

            // Descuentos a nivel de línea
            $table->decimal('descuento_porcentaje', 5, 2)->default(0);
            $table->decimal('descuento_importe', 10, 2)->default(0);
            $table->decimal('base_imponible', 10, 2); // subtotal - descuento

            // Impuestos
            $table->decimal('tipo_iva', 5, 2)->default(10); // % IVA (10, 21, 4, 0)
            $table->decimal('cuota_iva', 10, 2)->default(0); // base_imponible * (tipo_iva/100)
            $table->decimal('tipo_recargo', 5, 2)->default(0); // Recargo de equivalencia
            $table->decimal('cuota_recargo', 10, 2)->default(0);

            // Total de la línea
            $table->decimal('total_linea', 10, 2); // base_imponible + cuota_iva + cuota_recargo

            // Referencia a adiciones si aplica
            $table->json('adiciones')->nullable(); // Si el producto tiene extras

            $table->timestamps();

            // Índices
            $table->index('factura_id');
            $table->index('producto_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factura_lineas');
    }
};
