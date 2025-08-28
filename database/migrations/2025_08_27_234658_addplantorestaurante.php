<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('restaurantes', function (Blueprint $t) {
            // Solo 2 opciones; legacy = NULL
            $t->enum('plan', ['basic', 'advanced'])
              ->nullable()
              ->default(null)
              ->index();
        });
    }
    public function down() {
        Schema::table('restaurantes', function (Blueprint $t) {
            $t->dropColumn('plan');
        });
    }
};
