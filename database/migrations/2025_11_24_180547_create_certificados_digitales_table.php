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
        Schema::create('certificados_digitales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->onDelete('cascade');

            // Información del certificado
            $table->string('nombre_archivo_original');
            $table->string('ruta_archivo'); // Ruta encriptada del certificado
            $table->text('password_encriptado'); // Contraseña del certificado encriptada

            // Validación del certificado
            $table->string('nif_certificado'); // NIF asociado al certificado
            $table->string('titular_certificado'); // Nombre del titular
            $table->date('fecha_expedicion')->nullable();
            $table->date('fecha_caducidad');
            $table->boolean('valido')->default(false);
            $table->text('detalles_validacion')->nullable(); // JSON con detalles de validación

            // Estado
            $table->boolean('activo')->default(true);
            $table->timestamp('ultimo_uso')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['restaurante_id', 'activo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificados_digitales');
    }
};
