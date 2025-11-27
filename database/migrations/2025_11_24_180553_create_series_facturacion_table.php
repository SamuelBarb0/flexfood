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
        Schema::create('series_facturacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->onDelete('cascade');

            // Información de la serie
            $table->string('codigo_serie', 20); // Ej: FF-2024, TPV-2024
            $table->string('nombre')->nullable(); // Nombre descriptivo
            $table->text('descripcion')->nullable();

            // Configuración de numeración
            $table->integer('ultimo_numero')->default(0);
            $table->integer('numero_inicial')->default(1);
            $table->string('prefijo')->nullable(); // Ej: FF, TPV
            $table->string('sufijo')->nullable();
            $table->integer('digitos')->default(6); // Ceros a la izquierda

            // Tipo de serie
            $table->enum('tipo', ['principal', 'secundaria', 'rectificativa'])->default('principal');
            $table->enum('punto_venta', ['tpv', 'online', 'delivery', 'general'])->default('general');

            // Estado
            $table->boolean('activa')->default(true);
            $table->boolean('es_principal')->default(false); // Solo una serie principal por restaurante

            // Año fiscal (opcional, para series anuales)
            $table->year('ano_fiscal')->nullable();

            $table->timestamps();

            // Índices
            $table->unique(['restaurante_id', 'codigo_serie']);
            $table->index(['restaurante_id', 'activa']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('series_facturacion');
    }
};
