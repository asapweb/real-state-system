<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('charge_types', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();   // RENT, ADJ_DIFF_DEBIT, ...
            $table->string('name');
            $table->boolean('is_active')->default(true);

            // Impact per side: add|subtract|info|hidden
            $table->string('tenant_impact', 10)->default('hidden')->index();
            $table->string('owner_impact', 10)->default('hidden')->index();

            $table->boolean('requires_service_type')->default(false);
            $table->boolean('requires_service_period')->default(false);
            $table->string('requires_counterparty', 12)->nullable();   // tenant|owner|supplier|null
            $table->string('currency_policy', 24)->default('CONTRACT_CURRENCY');

            $table->timestamps();

            $table->index(['is_active', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charge_types');
    }
};
