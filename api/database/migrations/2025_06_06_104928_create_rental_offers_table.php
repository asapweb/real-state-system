<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rental_offers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')->constrained()->cascadeOnDelete();

            // Condiciones principales
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('ARS');
            $table->integer('duration_months');
            $table->date('availability_date')->nullable();

            // Expensas
            $table->decimal('common_expenses_amount', 10, 2)->nullable();

            // Sellado
            $table->boolean('seal_required')->default(false);
            $table->decimal('seal_amount', 10, 2)->nullable();
            $table->string('seal_currency', 3)->default('ARS');
            $table->decimal('seal_percentage_owner', 5, 2)->nullable();
            $table->decimal('seal_percentage_tenant', 5, 2)->nullable();

            // Seguro
            $table->boolean('includes_insurance')->default(false);
            $table->decimal('insurance_quote_amount', 10, 2)->nullable();

            // Políticas
            $table->text('commission_policy')->nullable();
            $table->text('deposit_policy')->nullable();
            $table->boolean('allow_pets')->default(false);

            // Estado y publicación
            $table->string('status', 20)->default('draft'); // Enum en Laravel
            $table->timestamp('published_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_offers');
    }
};
