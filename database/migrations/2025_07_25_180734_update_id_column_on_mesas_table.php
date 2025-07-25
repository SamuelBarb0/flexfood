<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ Primero quitamos AUTO_INCREMENT del campo id
        DB::statement('ALTER TABLE mesas MODIFY id BIGINT UNSIGNED NOT NULL');

        // ✅ Luego quitamos la clave primaria
        DB::statement('ALTER TABLE mesas DROP PRIMARY KEY');

        // ✅ Finalmente la volvemos a poner (sin auto_increment)
        DB::statement('ALTER TABLE mesas ADD PRIMARY KEY (id)');
    }

    public function down(): void
    {
        // 🔁 Revertir el cambio: quitar la PK, volver a poner auto_increment y restaurar PK
        DB::statement('ALTER TABLE mesas DROP PRIMARY KEY');
        DB::statement('ALTER TABLE mesas MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        DB::statement('ALTER TABLE mesas ADD PRIMARY KEY (id)');
    }
};
