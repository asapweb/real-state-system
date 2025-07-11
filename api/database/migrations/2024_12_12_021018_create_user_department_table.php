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
        Schema::create('department_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null'); // Usuario que creó
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null'); // Usuario que actualizó
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_user');
    }
};
