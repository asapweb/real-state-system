<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('property_owners', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();

            $table->decimal('ownership_percentage', 5, 2); // Ej: 50.00

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_owners');
    }
};
