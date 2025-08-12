<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Agregar la columna (nullable al principio para poder poblarla)
        Schema::table('ordenes', function (Blueprint $table) {
            $table->unsignedBigInteger('restaurante_id')->nullable()->after('id');
        });

        // 2) Poblar desde mesas (si la mesa pertenece a un restaurante)
        //    MySQL: UPDATE con JOIN
        DB::statement('
            UPDATE ordenes o
            LEFT JOIN mesas m ON m.id = o.mesa_id
            SET o.restaurante_id = m.restaurante_id
            WHERE o.restaurante_id IS NULL
              AND m.restaurante_id IS NOT NULL
        ');

        // 3) Asegurar que exista un restaurante por defecto si hiciera falta
        //    (Usamos el primero que exista; si no hay, creamos "flexfood")
        $defaultId = DB::table('restaurantes')->min('id');
        if (is_null($defaultId)) {
            $defaultId = DB::table('restaurantes')->insertGetId([
                'nombre'      => 'flexfood',
                'slug'        => 'flexfood',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // 4) Rellenar los que sigan NULL con el restaurante por defecto
        DB::table('ordenes')->whereNull('restaurante_id')->update(['restaurante_id' => $defaultId]);

        // 5) Volver la columna NOT NULL (sin DBAL)
        DB::statement('ALTER TABLE ordenes MODIFY restaurante_id BIGINT UNSIGNED NOT NULL');

        // 6) Agregar la FK
        Schema::table('ordenes', function (Blueprint $table) {
            $table->foreign('restaurante_id', 'ordenes_restaurante_id_foreign')
                ->references('id')->on('restaurantes')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Quitar FK y columna
        Schema::table('ordenes', function (Blueprint $table) {
            // Evitar errores si no existiera la FK (por seguridad)
            try {
                $table->dropForeign('ordenes_restaurante_id_foreign');
            } catch (\Throwable $e) {
                // silencioso
            }

            if (Schema::hasColumn('ordenes', 'restaurante_id')) {
                $table->dropColumn('restaurante_id');
            }
        });
    }
};
