<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * Run the migrations.
         */
        Schema::create('nationalities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();          // Ej: Argentino
            $table->string('iso_code', 3)->nullable();      // Ej: ARG
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nationalities');
    }
};
