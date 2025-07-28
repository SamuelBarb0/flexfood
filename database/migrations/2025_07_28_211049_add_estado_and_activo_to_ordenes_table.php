<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes', function (Blueprint $table) {
            $table->tinyInteger('estado')->default(0); // 0: pendiente, 1: en proceso, 2: entregado
            $table->boolean('activo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('ordenes', function (Blueprint $table) {
            $table->dropColumn(['estado', 'activo']);
        });
    }
};
