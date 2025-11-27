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
            // Datos fiscales básicos
            $table->string('razon_social')->nullable()->after('nombre');
            $table->string('nombre_comercial')->nullable()->after('razon_social');
            $table->string('nif')->nullable()->unique()->after('nombre_comercial');

            // Dirección fiscal
            $table->string('direccion_fiscal')->nullable()->after('nif');
            $table->string('municipio')->nullable()->after('direccion_fiscal');
            $table->string('provincia')->nullable()->after('municipio');
            $table->string('codigo_postal', 10)->nullable()->after('provincia');
            $table->string('pais')->default('España')->after('codigo_postal');

            // Información fiscal
            $table->enum('regimen_iva', ['general', 'simplificado', 'criterio_caja'])->nullable()->after('pais');
            $table->string('epigrafe_iae')->nullable()->after('regimen_iva');

            // Contacto fiscal
            $table->string('email_fiscal')->nullable()->after('epigrafe_iae');
            $table->string('telefono_fiscal')->nullable()->after('email_fiscal');

            // Estado de habilitación fiscal
            $table->boolean('fiscal_habilitado')->default(false)->after('telefono_fiscal');
            $table->timestamp('fiscal_habilitado_at')->nullable()->after('fiscal_habilitado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurantes', function (Blueprint $table) {
            $table->dropColumn([
                'razon_social',
                'nombre_comercial',
                'nif',
                'direccion_fiscal',
                'municipio',
                'provincia',
                'codigo_postal',
                'pais',
                'regimen_iva',
                'epigrafe_iae',
                'email_fiscal',
                'telefono_fiscal',
                'fiscal_habilitado',
                'fiscal_habilitado_at'
            ]);
        });
    }
};
