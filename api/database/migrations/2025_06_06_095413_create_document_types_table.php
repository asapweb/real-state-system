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
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();       // Ej: DNI, CUIT, Pasaporte
            $table->string('code_afip', 10)->nullable(); // Ej: 96, 80 (para interoperar con AFIP)
            $table->string('description', 150)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
