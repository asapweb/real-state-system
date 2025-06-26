<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rental_applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_offer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('applicant_id')->constrained('clients')->cascadeOnDelete();

            // Condiciones de seguro
            $table->string('insurance_responsible')->nullable();
            $table->date('insurance_required_from')->nullable();

            // Estado y observaciones
            $table->string('status', 20)->default('draft'); // Enum Laravel
            $table->text('notes')->nullable();

            // Reserva (puede ser reemplazado por payments más adelante)
            $table->decimal('reservation_amount', 10, 2)->nullable();
            $table->string('currency', 3)->default('ARS');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_applications');
    }
};
