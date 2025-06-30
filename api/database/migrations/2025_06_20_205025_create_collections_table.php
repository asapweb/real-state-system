<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')->constrained('clients');
            $table->foreignId('contract_id')->nullable()->constrained('contracts');

            $table->boolean('is_automatic')->default(false);

            $table->string('status', 20)->default('pending');
            $table->string('currency', 3)->default('ARS');
            $table->date('issue_date');
            $table->date('due_date');
            $table->string('period')->nullable(); // ej: '2025-06'
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->datetime('paid_at')->nullable();;
            $table->foreignId('paid_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['contract_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
