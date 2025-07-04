<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Tabla de tipos de alícuotas de IVA (tax_rates)
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('rate', 5, 2);
            $table->boolean('is_default')->default(false);
            $table->boolean('included_in_vat_detail')->default(true);
            $table->timestamps();
        });

        // Tabla de tipos de comprobante (voucher_types)
        Schema::create('voucher_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name', 10);
            $table->string('letter', 2);
            $table->unsignedSmallInteger('afip_id')->nullable();
            $table->boolean('credit')->default(false);
            $table->boolean('affects_account')->default(true);
            $table->boolean('affects_cash')->default(false);
            $table->unsignedTinyInteger('order')->default(0);
            $table->timestamps();
        });

        // Tabla de puntos de venta (sale_points)
        Schema::create('sale_points', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedInteger('number');
            $table->boolean('electronic')->default(true);
            $table->timestamps();
        });

        // Tabla de medios de pago (payment_methods)
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('handled_by_agency')->default(true);
            $table->timestamps();
        });

        // Tabla de talonarios (booklets)
        Schema::create('booklets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('prefix', 10);
            $table->foreignId('voucher_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_point_id')->nullable()->constrained()->nullOnDelete();
            $table->string('default_currency', 3)->default('ARS');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();
        });

        // Tabla de comprobantes (vouchers)
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booklet_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('number');
            $table->date('issue_date');
            $table->date('period')->nullable();
            $table->date('due_date')->nullable();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['draft', 'issued', 'cancelled'])->default('draft');
            $table->string('currency', 3);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->string('cae')->nullable();
            $table->date('cae_expires_at')->nullable();
            $table->decimal('subtotal_taxed', 12, 2)->nullable();
            $table->decimal('subtotal_untaxed', 12, 2)->nullable();
            $table->decimal('subtotal_exempt', 12, 2)->nullable();
            $table->decimal('subtotal_vat', 12, 2)->nullable();
            $table->decimal('subtotal', 12, 2)->nullable();
            $table->timestamps();
            $table->unique(['booklet_id', 'number']);
        });

        // Tabla de ítems del comprobante (voucher_items)
        Schema::create('voucher_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->foreignId('tax_rate_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('subtotal_with_vat', 12, 2);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        // Tabla de imputaciones entre comprobantes (voucher_applications)
        Schema::create('voucher_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->cascadeOnDelete();
            $table->foreignId('applied_to_id')->constrained('vouchers')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();
            $table->unique(['voucher_id', 'applied_to_id']);
        });

        // Tabla de pagos realizados por voucher (voucher_payments)
        Schema::create('voucher_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Tabla de movimientos de cuenta corriente (account_movements)
        Schema::create('account_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('ARS');
            $table->boolean('is_initial')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        // Tabla de movimientos de caja (cash_movements)
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('ARS');
            $table->string('reference')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('account_movements');
        Schema::dropIfExists('voucher_payments');
        Schema::dropIfExists('voucher_applications');
        Schema::dropIfExists('voucher_items');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('booklets');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('sale_points');
        Schema::dropIfExists('voucher_types');
        Schema::dropIfExists('tax_rates');
    }
};
