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
        Schema::table('facturas', function (Blueprint $table) {
            // QR de VeriFactu (obligatorio)
            $table->text('verifactu_qr_url')->nullable()->after('verifactu_id');
            $table->text('verifactu_qr_data')->nullable()->after('verifactu_qr_url');

            // Respuesta AEAT (asíncrona)
            $table->enum('aeat_estado', ['pendiente', 'aceptada', 'rechazada'])->default('pendiente')->after('verifactu_error');
            $table->text('aeat_response')->nullable()->after('aeat_estado');
            $table->timestamp('aeat_fecha_respuesta')->nullable()->after('aeat_response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn([
                'verifactu_qr_url',
                'verifactu_qr_data',
                'aeat_estado',
                'aeat_response',
                'aeat_fecha_respuesta',
            ]);
        });
    }
};
