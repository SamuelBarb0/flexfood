<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Asegurar que la columna id sea AUTO_INCREMENT
        // (MySQL requiere MODIFY en crudo si la columna ya existe)
        DB::statement('ALTER TABLE mesas MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

        Schema::table('mesas', function (Blueprint $table) {
            // Asegurar tipos y nulabilidad sin romper datos existentes
            if (Schema::hasColumn('mesas', 'nombre')) {
                $table->unsignedInteger('nombre')->change();
            }

            if (Schema::hasColumn('mesas', 'codigo_qr')) {
                $table->string('codigo_qr')->nullable()->change();
            } else {
                $table->string('codigo_qr')->nullable();
            }

            // (Opcional pero recomendado) índice único por restaurante + número de mesa
            // Antes de crearlo, intenta eliminar si existe con otro nombre
        });

        // Crear índice único si no existe ya
        // MySQL no tiene "create if not exists" en índices, así que comprobamos vía información del esquema
        $exists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', 'mesas')
            ->where('index_name', 'mesas_restaurante_nombre_unique')
            ->exists();

        if (! $exists) {
            DB::statement('CREATE UNIQUE INDEX mesas_restaurante_nombre_unique ON mesas (restaurante_id, nombre)');
        }
    }

    public function down(): void
    {
        // Revertir el índice único (si existe)
        try {
            DB::statement('DROP INDEX mesas_restaurante_nombre_unique ON mesas');
        } catch (\Throwable $e) {
            // ignorar si no existe
        }

        // Imposible "des-auto-incrementar" de forma segura sin conocer el estado previo,
        // así que solo dejamos nota. Si necesitas revertir exactamente, crea otra migración específica.
    }
};
