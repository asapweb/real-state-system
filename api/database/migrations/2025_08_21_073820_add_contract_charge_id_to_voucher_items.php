<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('voucher_items') && !Schema::hasColumn('voucher_items', 'contract_charge_id')) {
            Schema::table('voucher_items', function (Blueprint $table) {
                $table->foreignId('contract_charge_id')
                    ->nullable()
                    ->after('voucher_id')
                    ->constrained('contract_charges')
                    ->nullOnDelete();

                // Evita duplicar el mismo cargo en el mismo voucher
                $table->unique(['voucher_id', 'contract_charge_id'], 'uq_vi_voucher_charge');

                // Índice auxiliar para búsquedas
                $table->index(['voucher_id', 'contract_charge_id'], 'idx_vi_voucher_charge');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('voucher_items') && Schema::hasColumn('voucher_items', 'contract_charge_id')) {
            Schema::table('voucher_items', function (Blueprint $table) {
                $table->dropIndex('idx_vi_voucher_charge');
                $table->dropUnique('uq_vi_voucher_charge');
                $table->dropForeign(['contract_charge_id']);
                $table->dropColumn('contract_charge_id');
            });
        }
    }
};
