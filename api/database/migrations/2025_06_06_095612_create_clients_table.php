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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            $table->string('type', 20)->index();
            $table->string('name', 150); // Nombre completo o razón social
            $table->string('last_name', 100)->nullable(); // Sólo para personas físicas
            $table->string('gender')->nullable(); // Enum Laravel

            $table->foreignId('document_type_id')->nullable()->constrained('document_types');
            $table->string('document_number', 20)->nullable()->index();
            $table->boolean('no_document')->default(false);

            $table->foreignId('tax_document_type_id')->nullable()->constrained('document_types');
            $table->string('tax_document_number', 20)->nullable()->index();
            $table->foreignId('tax_condition_id')->nullable()->constrained('tax_conditions');

            $table->string('email', 100)->nullable();
            $table->string('phone', 20)->nullable()->index(); // Formato internacional (E.164)
            $table->string('address', 200)->nullable();

            $table->foreignId('civil_status_id')->nullable()->constrained('civil_statuses');
            $table->foreignId('nationality_id')->nullable()->constrained('nationalities');

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
