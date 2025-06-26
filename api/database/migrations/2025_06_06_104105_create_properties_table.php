<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_type_id')->constrained('property_types');

            // Dirección
            $table->string('street', 100);
            $table->string('number', 10)->nullable();
            $table->string('floor', 10)->nullable();
            $table->string('apartment', 10)->nullable();
            $table->string('postal_code', 20)->nullable();

            // Ubicación geográfica
            $table->foreignId('country_id')->constrained('countries');
            $table->foreignId('state_id')->constrained('states');
            $table->foreignId('city_id')->constrained('cities');
            $table->foreignId('neighborhood_id')->constrained('neighborhoods');

            $table->string('tax_code')->nullable();          // Partida
            $table->string('cadastral_reference')->nullable(); // Nomenclatura catastral
            $table->string('registry_number')->nullable();   // Matrícula

            // Detalles técnicos / comerciales
            $table->boolean('has_parking')->default(false);
            $table->text('parking_details')->nullable();
            $table->boolean('allows_pets')->default(false);
            $table->string('iva_condition')->nullable();

            // Estado
            $table->string('status', 20)->default('draft'); // Enum manejado desde Laravel

            $table->text('observations')->nullable();

            // Auditoría
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
