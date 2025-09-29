<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contract_charges', function (Blueprint $table) {
            $table->id();

            // Relaciones principales
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('charge_type_id')->constrained('charge_types')->restrictOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained('service_types')->restrictOnDelete();

            // Contraparte (si el tipo lo exige) -> contract_clients (role: tenant|owner)
            $table->foreignId('counterparty_contract_client_id')
                  ->nullable()
                  ->constrained('contract_clients')
                  ->nullOnDelete();

            $table->decimal('amount', 14, 2)->unsigned();
            $table->string('currency', 3)->default('ARS')->index();

            // Fechas núcleo
            $table->date('effective_date')->index();       // período al que corresponde (sugerencia: 1º de mes)
            $table->date('due_date')->nullable()->index(); // vencimiento informativo para el lado tenant

            // Periodificación / servicios (si aplica)
            $table->date('service_period_start')->nullable();
            $table->date('service_period_end')->nullable();
            $table->date('invoice_date')->nullable();

            // Vouchers vinculados a LIQUIDACIONES (no a recibos)
            $table->foreignId('tenant_liquidation_voucher_id')
                  ->nullable()->constrained('vouchers')->nullOnDelete(); // Liquidación al inquilino (AR)
            $table->foreignId('owner_liquidation_voucher_id')
                  ->nullable()->constrained('vouchers')->nullOnDelete();  // Liquidación al propietario

            // Marcas de "asentado" (cuando la liquidación pasa a NO-DRAFT)
            $table->timestamp('tenant_liquidation_settled_at')->nullable();
            $table->timestamp('owner_liquidation_settled_at')->nullable();

            // Meta
            $table->text('description')->nullable();

            // Cancelación (app mantiene is_canceled)
            $table->timestamp('canceled_at')->nullable();
            $table->foreignId('canceled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('canceled_reason')->nullable();
            $table->boolean('is_canceled')->default(false)->index();

            $table->timestamps();

            // Índices útiles
            $table->index(['contract_id', 'effective_date'], 'idx_cc_contract_effective');
            $table->index(['contract_id', 'currency'], 'idx_cc_contract_currency');
            $table->index(['service_period_start', 'service_period_end'], 'idx_cc_service_period');
            $table->index(['charge_type_id'], 'idx_cc_type');
            $table->index(['tenant_liquidation_voucher_id'], 'idx_cc_tenant_liq');
            $table->index(['owner_liquidation_voucher_id'], 'idx_cc_owner_liq');

            // (Opcional) compuestos para queries frecuentes
            $table->index(['contract_id','charge_type_id','effective_date'], 'idx_cc_contract_type_period');
        });

        // Checks opcionales (ignorar si no están soportados)
        try {
            $driver = DB::getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME) ?? null;
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE contract_charges
                    ADD CONSTRAINT chk_cc_amount_nonneg CHECK (amount >= 0)");
                DB::statement("ALTER TABLE contract_charges
                    ADD CONSTRAINT chk_cc_service_period CHECK (
                        service_period_start IS NULL
                        OR service_period_end IS NULL
                        OR service_period_end >= service_period_start
                    )");
                DB::statement("ALTER TABLE contract_charges
                    ADD CONSTRAINT chk_cc_due_ge_effective CHECK (
                        due_date IS NULL OR due_date >= effective_date
                    )");
            }
        } catch (\Throwable $e) {
            // Silencioso: algunos engines/versions no soportan CHECK
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_charges');
    }
};
