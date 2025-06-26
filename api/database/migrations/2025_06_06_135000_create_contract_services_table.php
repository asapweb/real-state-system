<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contract_services', function (Blueprint $table) {
    $table->id();

    $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
    $table->string('service_type'); // Enum: electricity, water, gas, etc.
    $table->string('account_number')->nullable();
    $table->string('provider_name')->nullable();
    $table->string('owner_name')->nullable(); // titular
    $table->boolean('is_active')->default(true);
    $table->boolean('has_debt')->default(false);
    $table->decimal('debt_amount', 10, 2)->nullable();
    $table->string('paid_by')->nullable(); // quien paga (inquilino, propietario, etc.)
    $table->text('notes')->nullable();

    $table->timestamps();
});

    }

    public function down(): void
    {
        Schema::dropIfExists('contract_services');
    }
};
