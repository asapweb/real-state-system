<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_application_clients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rental_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();

            $table->string('role'); // Ej: 'guarantor', 'co-applicant'
            $table->string('relationship')->nullable(); // vínculo con el solicitante

            $table->string('occupation')->nullable();
            $table->string('employer')->nullable();
            $table->decimal('income', 12, 2)->nullable();
            $table->string('currency', 3)->default('ARS');
            $table->string('seniority')->nullable();
            $table->boolean('is_property_owner')->default(false);

            $table->string('marital_status')->nullable();
            $table->string('spouse_name')->nullable();
            $table->string('nationality')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_application_clients');
    }
};
