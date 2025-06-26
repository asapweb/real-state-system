<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rental_offer_service_statuses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rental_offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_service_id')->constrained()->cascadeOnDelete();

            $table->boolean('is_active')->default(true);
            $table->boolean('has_debt')->default(false);
            $table->decimal('debt_amount', 10, 2)->nullable();
            $table->string('paid_by', 20)->nullable(); // Enum: tenant, owner, agency
            $table->string('notes')->nullable();

            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('rental_offer_service_statuses');
    }
};
