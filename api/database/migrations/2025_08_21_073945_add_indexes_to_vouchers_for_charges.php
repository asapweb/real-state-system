<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('vouchers')) {
            Schema::table('vouchers', function (Blueprint $table) {
                if (!Schema::hasColumn('vouchers', 'currency')) {
                    $table->string('currency', 3)->nullable()->after('client_id')->index();
                }
                if (Schema::hasColumn('vouchers', 'contract_id')
                    && Schema::hasColumn('vouchers', 'currency')
                    && Schema::hasColumn('vouchers', 'status')) {
                    $table->index(['contract_id', 'currency', 'status'], 'idx_v_contract_currency_status');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vouchers')) {
            Schema::table('vouchers', function (Blueprint $table) {
                if (Schema::hasColumn('vouchers', 'contract_id')
                    && Schema::hasColumn('vouchers', 'currency')
                    && Schema::hasColumn('vouchers', 'status')) {
                    $table->dropIndex('idx_v_contract_currency_status');
                }
                // No borramos la columna currency si ya existía previamente en tu esquema.
            });
        }
    }
};
