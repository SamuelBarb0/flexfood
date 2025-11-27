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
        Schema::table('restaurantes', function (Blueprint $table) {
            // Modelo de Representación (formulario BOE requerido antes de primera factura)
            $table->boolean('modelo_representacion_firmado')->default(false)->after('verifactu_token_expires_at');
            $table->string('modelo_representacion_archivo')->nullable()->after('modelo_representacion_firmado');
            $table->timestamp('modelo_representacion_fecha')->nullable()->after('modelo_representacion_archivo');
            $table->text('modelo_representacion_observaciones')->nullable()->after('modelo_representacion_fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurantes', function (Blueprint $table) {
            $table->dropColumn([
                'modelo_representacion_firmado',
                'modelo_representacion_archivo',
                'modelo_representacion_fecha',
                'modelo_representacion_observaciones',
            ]);
        });
    }
};
