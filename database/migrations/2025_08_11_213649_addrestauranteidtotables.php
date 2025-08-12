<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // categorias
        Schema::table('categorias', function (Blueprint $t) {
            if (!Schema::hasColumn('categorias','restaurante_id')) {
                $t->foreignId('restaurante_id')->nullable()->after('id')->constrained('restaurantes')->nullOnDelete();
            }
        });

        // productos
        Schema::table('productos', function (Blueprint $t) {
            if (!Schema::hasColumn('productos','restaurante_id')) {
                $t->foreignId('restaurante_id')->nullable()->after('id')->constrained('restaurantes')->nullOnDelete();
            }
        });

        // adiciones
        Schema::table('adiciones', function (Blueprint $t) {
            if (!Schema::hasColumn('adiciones','restaurante_id')) {
                $t->foreignId('restaurante_id')->nullable()->after('id')->constrained('restaurantes')->nullOnDelete();
            }
        });

        // site_settings
        Schema::table('site_settings', function (Blueprint $t) {
            if (!Schema::hasColumn('site_settings','restaurante_id')) {
                $t->foreignId('restaurante_id')->nullable()->after('id')->constrained('restaurantes')->nullOnDelete();
            }
        });

        // users (trabajadores)
        Schema::table('users', function (Blueprint $t) {
            if (!Schema::hasColumn('users','restaurante_id')) {
                $t->foreignId('restaurante_id')->nullable()->after('id')->constrained('restaurantes')->nullOnDelete();
            }
        });
    }

    public function down(): void {
        foreach (['categorias','productos','adiciones','site_settings','users'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (Schema::hasColumn($table,'restaurante_id')) {
                    $t->dropForeign([$table === 'users' ? 'restaurante_id' : 'restaurante_id']);
                    $t->dropColumn('restaurante_id');
                }
            });
        }
    }
};