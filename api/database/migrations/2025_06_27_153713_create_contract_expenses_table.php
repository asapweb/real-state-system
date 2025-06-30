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
        Schema::create('contract_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('service_type'); // ej. 'expensas', 'agua', 'gas'
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('ARS');
            $table->string('period', 7); // almacena '2025-07'
            $table->date('due_date')->nullable(); // vencimiento del gasto
            $table->string('paid_by', 20)->default('tenant'); // 'tenant', 'agency', 'owner'
            $table->boolean('is_paid')->default(false);
            $table->date('paid_at')->nullable();
            $table->boolean('included_in_collection')->default(false); // si ya fue cobrado
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_expenses');
    }
};
