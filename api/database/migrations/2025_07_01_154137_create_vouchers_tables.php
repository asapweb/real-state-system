<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('afip_operation_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('afip_id');
            $table->boolean('is_default')->default(0);
        });

        DB::table('afip_operation_types')->insert(['id' => 1, 'name' =>'Productos', 'afip_id' => 1]);
		DB::table('afip_operation_types')->insert(['id' => 2, 'name' =>'Servicios', 'afip_id' => 2]);
		DB::table('afip_operation_types')->insert(['id' => 3, 'name' =>'Productos y Servicios', 'afip_id' => 3, 'is_default' => 1]);

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

        // Tabla de cuentas de caja (cash_accounts)
        Schema::create('cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['cash', 'bank', 'virtual']);
            $table->string('currency', 3)->default('ARS');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabla de medios de pago (payment_methods)
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('requires_reference')->default(false);
            $table->foreignId('default_cash_account_id')->nullable()->constrained('cash_accounts')->nullOnDelete();
            $table->string('code')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('handled_by_agency')->default(true);
            $table->timestamps();
        });

        // Tabla de talonarios (booklets)
        Schema::create('booklets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('voucher_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_point_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('collection_booklet_id')->nullable()->constrained('booklets')->nullOnDelete();
            $table->foreignId('settlement_booklet_id')->nullable()->constrained('booklets')->nullOnDelete();
            $table->string('default_currency', 3)->default('ARS');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->boolean('default')->default(false);
            $table->timestamps();

            $table->unique(['voucher_type_id', 'sale_point_id']);
        });

        // Tabla de comprobantes (vouchers)
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booklet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voucher_type_id')->constrained()->cascadeOnDelete();
            $table->string('voucher_type_short_name', 10)->nullable();
            $table->string('voucher_type_letter', 2)->nullable();
            $table->unsignedInteger('sale_point_number')->nullable();
            $table->unsignedBigInteger('number')->nullable();
            $table->date('issue_date');
            $table->date('period')->nullable();
            $table->boolean('generated_from_collection')->default(false);
            $table->date('due_date')->nullable();
            $table->date('service_date_from')->nullable();
            $table->date('service_date_to')->nullable();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();

            // Client data snapshot
            $table->string('client_name')->nullable();
            $table->string('client_address')->nullable();
            $table->string('client_document_type_name')->nullable();
            $table->string('client_document_number')->nullable();
            $table->string('client_tax_condition_name')->nullable();
            $table->string('client_tax_id_number')->nullable();

            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('afip_operation_type_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['draft', 'issued', 'cancelled'])->default('draft');
            $table->string('currency', 3);
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->string('cae')->nullable();
            $table->date('cae_expires_at')->nullable();
            $table->decimal('subtotal_taxed', 15, 2)->nullable();
            $table->decimal('subtotal_untaxed', 15, 2)->nullable();
            $table->decimal('subtotal_exempt', 15, 2)->nullable();
            $table->decimal('subtotal_vat', 15, 2)->nullable();
            $table->decimal('subtotal_other_taxes', 15, 2)->nullable();
            $table->decimal('subtotal', 15, 2)->nullable();
            $table->decimal('total', 15, 2)->default(0);
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

        Schema::create('voucher_associations', function (Blueprint $table) {
            $table->id();

            // Comprobante que realiza el ajuste (ej. NC o ND)
            $table->foreignId('voucher_id')->constrained('vouchers')->cascadeOnDelete();

            // Comprobante que está siendo ajustado (ej. FAC o ND)
            $table->foreignId('associated_voucher_id')->constrained('vouchers')->cascadeOnDelete();

            $table->timestamps();

            // Evita duplicar asociaciones
            $table->unique(['voucher_id', 'associated_voucher_id']);
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
            $table->foreignId('cash_account_id')->nullable()->constrained('cash_accounts')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('reference')->nullable();
            $table->timestamps();
        });

        // Tabla de movimientos de cuenta corriente (account_movements)
        Schema::create('account_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained()->restrictOnDelete();
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
            $table->foreignId('cash_account_id')->constrained('cash_accounts')->cascadeOnDelete();
            $table->enum('direction', ['in', 'out']);
            $table->foreignId('voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->string('currency', 3)->default('ARS');
            $table->decimal('amount', 12, 2);
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
        Schema::dropIfExists('cash_accounts');
    }
};
