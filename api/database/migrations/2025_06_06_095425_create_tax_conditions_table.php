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
        Schema::create('tax_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();        // Ej: Responsable Inscripto
            $table->string('code_afip', 10)->nullable();  // Ej: 1, 2, 3... si usás códigos de AFIP
            $table->string('description', 150)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true); // Habilitado/deshabilitado
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_conditions');
    }
};
