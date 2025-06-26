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
        Schema::create('attachment_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('context')->nullable(); // Ej: 'contracts', 'clients', 'properties'
            $table->boolean('is_required')->default(false); // Para validación de checklist
            $table->boolean('is_default')->default(false);  // Para preselección
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachment_categories');
    }
};
