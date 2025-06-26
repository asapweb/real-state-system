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
            $table->timestamp('applied_at')->nullable(); // Fecha en que se aplicó el ajuste, si corresponde
            $table->text('notes')->nullable(); // Descripción libre o nombre del índice

            $table->foreignId('index_type_id')->nullable()->constrained('index_types')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_adjustments');
    }
};
