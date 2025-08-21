<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_application_id')->nullable()->constrained()->nullOnDelete();

            // Vigencia y monto
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('monthly_amount', 10, 2);
            $table->string('currency', 3)->default('ARS');
            $table->tinyInteger('payment_day')->nullable();

            // Prorrateo
            $table->boolean('prorate_first_month')->default(false);
            $table->boolean('prorate_last_month')->default(false);

            // Comisión
            $table->string('commission_type')->default('none');
            $table->decimal('commission_amount', 10, 2)->nullable();
            $table->string('commission_payer')->nullable();
            $table->boolean('is_one_time')->default(false);

            // Seguro
            $table->boolean('insurance_required')->default(true);
            $table->decimal('insurance_amount', 10, 2)->nullable();
            $table->string('insurance_company_name')->nullable();

            // Liquidación al propietario
            $table->decimal('owner_share_percentage', 5, 2)->default(100.00);

            // Depósito de garantía
            $table->decimal('deposit_amount', 10, 2)->nullable();
            $table->string('deposit_currency', 3)->nullable();
            $table->string('deposit_type')->default('none');
            $table->string('deposit_holder')->nullable();

            // Punitorios
            $table->boolean('has_penalty')->default(false);
            $table->string('penalty_type')->default('none');
            $table->decimal('penalty_value', 10, 2)->nullable();
            $table->integer('penalty_grace_days')->default(0);

            // Estado y notas
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
