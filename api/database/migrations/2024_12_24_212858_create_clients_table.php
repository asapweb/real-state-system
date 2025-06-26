<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Schema::create('document_types', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('name')->unique(); // Document type name
        //     $table->enum('client_type', ['person', 'company', 'both'])->default('both'); // Entity type
        //     $table->timestamps();
        // });

        // Schema::create('clients', function (Blueprint $table) {
        //     $table->id();
        //     $table->enum('client_type', ['person', 'company'])->default('person'); // Client type
        //     $table->string('first_name')->nullable(); // First name (person)
        //     $table->string('last_name')->nullable(); // Last name (person)
        //     $table->string('company_name')->nullable(); // Company name (business)
        //     $table->string('business_name')->nullable(); // Business name (business)
        //     $table->foreignId('document_type_id')->nullable()->constrained('document_types'); // Document type
        //     $table->string('document_number', 50)->nullable(); // Document number (VARCHAR) - CORRECTED!
        //     $table->boolean('document_unknown')->default(false); // Document unknown
        //     $table->date('birth_date')->nullable(); // Birth date (person)
        //     $table->enum('gender', ['male', 'female', 'other'])->nullable(); // Gender (person)
        //     $table->string('cellphone', 25)->nullable(); // Phone number
        //     $table->string('email')->nullable(); // Email address
        //     $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Created by user
        //     $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null'); // Usuario que creó
        //     $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null'); // Usuario que actualizó
        //     $table->timestamps();

        //     // Indexes
        //     $table->unique(['document_type_id', 'document_number']); // Unique document type and number
        //     $table->index(['first_name', 'last_name']); // Index for name and last name search
        //     $table->index('business_name'); // Index for business name search
        //     $table->index('email'); // Index for email search
        //     $table->index('cellphone'); // Index for phone number search
        // });
    }

    public function down()
    {
        // Schema::dropIfExists('clients');
        // Schema::dropIfExists('document_types');
    }
};
