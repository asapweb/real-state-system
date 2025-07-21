<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Eliminar tablas de collections después de migrar a vouchers
        Schema::dropIfExists('collection_items');
        Schema::dropIfExists('collections');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recrear tabla collections
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending');
            $table->string('currency', 3);
            $table->date('issue_date');
            $table->date('due_date');
            $table->string('period', 7); // YYYY-MM
            $table->decimal('total_amount', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });

        // Recrear tabla collection_items
        Schema::create('collection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->string('description');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }
};
