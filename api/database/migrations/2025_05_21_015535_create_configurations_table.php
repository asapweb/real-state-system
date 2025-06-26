<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->index(); // Nuevo campo para agrupar
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->timestamps();

            // Índice compuesto para consultas rápidas por grupo y clave
            $table->unique(['group', 'key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('configurations');
    }
};
