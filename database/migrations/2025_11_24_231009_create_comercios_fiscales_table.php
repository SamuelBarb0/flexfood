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
        Schema::create('comercios_fiscales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->onDelete('cascade');

            // Datos fiscales del cliente/comercio
            $table->string('nif_cif', 20)->nullable();
            $table->string('razon_social')->nullable();
            $table->string('nombre_comercial')->nullable();
            $table->string('email')->nullable();
            $table->string('telefono', 20)->nullable();

            // Dirección fiscal
            $table->string('direccion')->nullable();
            $table->string('municipio')->nullable();
            $table->string('provincia')->nullable();
            $table->string('codigo_postal', 10)->nullable();
            $table->string('pais', 2)->default('ES'); // Código ISO 3166-1 alpha-2

            // Control
            $table->boolean('activo')->default(true);
            $table->text('notas')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['restaurante_id', 'nif_cif']);
            $table->index(['restaurante_id', 'activo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comercios_fiscales');
    }
};
