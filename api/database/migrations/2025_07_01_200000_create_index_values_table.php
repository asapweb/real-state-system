<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('index_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('index_type_id')->constrained('index_types')->cascadeOnDelete();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->date('effective_date')->nullable(); // para modo ratio
            $table->decimal('value', 10, 4)->nullable();
            $table->timestamps();

            // Índices únicos según el modo de cálculo
            $table->unique(['index_type_id', 'effective_date'], 'index_values_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('index_values');
    }
};
