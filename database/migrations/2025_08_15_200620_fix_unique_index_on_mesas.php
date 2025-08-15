<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $index): bool
    {
        $db = DB::getDatabaseName();
        return DB::table('information_schema.statistics')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    public function up(): void
    {
        // 1) Quitar el índice único viejo sobre 'nombre' si existe
        if ($this->indexExists('mesas', 'mesas_nombre_unique')) {
            Schema::table('mesas', function (Blueprint $table) {
                $table->dropUnique('mesas_nombre_unique');
            });
        }

        // 2) Crear el índice compuesto si NO existe (no intentamos borrar el que ya exista)
        if (! $this->indexExists('mesas', 'mesas_restaurante_nombre_unique')) {
            Schema::table('mesas', function (Blueprint $table) {
                $table->unique(['restaurante_id', 'nombre'], 'mesas_restaurante_nombre_unique');
            });
        }
    }

    public function down(): void
    {
        // (Opcional) Restaurar el índice único sobre 'nombre' si no existiera
        if (! $this->indexExists('mesas', 'mesas_nombre_unique')) {
            Schema::table('mesas', function (Blueprint $table) {
                $table->unique('nombre', 'mesas_nombre_unique');
            });
        }

        // No eliminamos el compuesto en down() porque podría estar siendo
        // usado por claves foráneas y fallaría. Si necesitas revertirlo,
        // primero elimina/ajusta las FKs que lo referencian.
    }
};
