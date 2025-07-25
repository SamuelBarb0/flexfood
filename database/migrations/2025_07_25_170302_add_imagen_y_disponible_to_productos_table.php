<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('imagen')->nullable()->after('descripcion');
            $table->boolean('disponible')->default(true)->after('imagen');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['imagen', 'disponible']);
        });
    }
};

