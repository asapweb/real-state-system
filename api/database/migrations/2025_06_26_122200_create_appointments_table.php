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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->datetime('start_at')->nullable();;
            $table->foreignId('booked_by')->nullable()->constrained(table: 'users');
            $table->datetime('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained(table: 'users');
            $table->datetime('attended_start_at')->nullable();
            $table->foreignId('attended_start_by')->nullable()->constrained(table: 'users');
            $table->datetime('attended_end_at')->nullable();
            $table->foreignId('attended_end_by')->nullable()->constrained(table: 'users');
            $table->datetime('attended_complete_at')->nullable();
            $table->boolean('is_unexpected')->default(false); // Indica si es un turno espontaneo
            $table->foreignId('department_id')->nullable()->constrained()->nullable();
            $table->foreignId('employee_id')->nullable()->constrained(table: 'users');
            $table->foreignId('client_id')->constrained(table: 'clients');
            $table->string('notes',1000)->nullable(); // Apellido (si es persona)
            $table->enum('status', ['booked', 'arrived', 'attended', 'seen', 'no_show', 'canceled', 'deleted']);

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null'); // Usuario que creó
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null'); // Usuario que actualizó
            $table->foreignId('deleted_by')->nullable()->constrained('users')->onDelete('set null'); // Usuario que actualizó
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
