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
        Schema::create('adicion_producto', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('adicion_id');
            $table->unsignedBigInteger('producto_id');
            $table->timestamps();

            $table->foreign('adicion_id')->references('id')->on('adiciones')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adicion_producto');
    }
};
