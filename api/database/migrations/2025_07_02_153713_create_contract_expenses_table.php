<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('contract_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained('service_types')->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('ARS');
            $table->date('effective_date'); // reemplaza 'period'
            $table->date('due_date')->nullable();
            $table->string('paid_by', 20); // tenant, owner, agency
            $table->string('responsible_party', 20); // tenant, owner
            $table->boolean('is_paid')->default(false);
            $table->date('paid_at')->nullable();
            $table->foreignId('generated_credit_note_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->foreignId('liquidation_voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->timestamp('settled_at')->nullable();
            $table->string('status', 20)->default('pending'); // pending, validated, billed, credited, liquidated, canceled
            $table->boolean('included_in_voucher')->default(false);
            $table->text('description')->nullable();
            $table->foreignId('voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_expenses');
    }
};
