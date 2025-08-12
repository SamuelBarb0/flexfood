<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mesas', function (Blueprint $table) {
            if (!Schema::hasColumn('mesas', 'restaurante_id')) {
                $table->unsignedBigInteger('restaurante_id')->nullable()->after('id');
            }
        });

        // Puedes agregar la FK aun siendo nullable (no rompe mientras haya NULLs)
        Schema::table('mesas', function (Blueprint $table) {
            // Evita duplicar constraint si corres varias veces
            $table->foreign('restaurante_id', 'mesas_restaurante_id_foreign')
                ->references('id')->on('restaurantes')
                ->onDelete('cascade');
        });

        // (Opcional) Índice único por restaurante+nombre.
        // Si aún tienes NULLs en restaurante_id, no estorba: los NULL no violan unicidad.
        Schema::table('mesas', function (Blueprint $table) {
            if (!Schema::hasColumn('mesas', 'nombre')) return;
            $table->unique(['restaurante_id', 'nombre'], 'mesas_restaurante_nombre_unique');
        });
    }

    public function down(): void
    {
        Schema::table('mesas', function (Blueprint $table) {
            try { $table->dropUnique('mesas_restaurante_nombre_unique'); } catch (\Throwable $e) {}
            try { $table->dropForeign('mesas_restaurante_id_foreign'); } catch (\Throwable $e) {}
            if (Schema::hasColumn('mesas', 'restaurante_id')) {
                $table->dropColumn('restaurante_id');
            }
        });
    }
};
