<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contract_adjustments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->date('effective_date'); // Desde cuándo aplica este ajuste

            $table->string('type');
            $table->decimal('value', 10, 2)->nullable(); // ej: $100k o 10%

            $table->decimal('base_amount', 12, 2)->nullable();
            $table->decimal('factor', 14, 8)->nullable(); // opcional pero recomendable
            // opcional de auditoría:
            $table->date('index_S_date')->nullable();
            $table->date('index_F_date')->nullable();
            $table->decimal('index_S_value', 14, 6)->nullable();
            $table->decimal('index_F_value', 14, 6)->nullable();


            $table->timestamp('applied_at')->nullable(); // Fecha en que se aplicó el ajuste, si corresponde
            $table->text('notes')->nullable(); // Descripción libre o nombre del índice

            $table->foreignId('index_type_id')->nullable()->constrained('index_types')->nullOnDelete();
            $table->decimal('applied_amount', 10, 2)->nullable(); // Valor del alquiler actualizado luego del ajuste
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_adjustments');
    }
};
