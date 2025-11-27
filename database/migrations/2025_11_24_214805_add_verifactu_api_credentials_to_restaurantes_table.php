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
            // Credenciales de API VeriFactu
            $table->string('verifactu_api_username')->nullable()->after('fiscal_habilitado_at');
            $table->text('verifactu_api_key_encrypted')->nullable()->after('verifactu_api_username');
            $table->text('verifactu_api_token')->nullable()->after('verifactu_api_key_encrypted');
            $table->timestamp('verifactu_token_expires_at')->nullable()->after('verifactu_api_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurantes', function (Blueprint $table) {
            $table->dropColumn([
                'verifactu_api_username',
                'verifactu_api_key_encrypted',
                'verifactu_api_token',
                'verifactu_token_expires_at',
            ]);
        });
    }
};
