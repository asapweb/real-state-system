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
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('collection_booklet_id')->nullable()->constrained('booklets')->nullOnDelete();
            $table->foreignId('settlement_booklet_id')->nullable()->constrained('booklets')->nullOnDelete();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['collection_booklet_id']);
            $table->dropForeign(['settlement_booklet_id']);
            $table->dropColumn(['collection_booklet_id', 'settlement_booklet_id']);
        });
    }
};
