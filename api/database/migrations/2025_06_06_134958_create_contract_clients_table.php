<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contract_clients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();

            $table->string('role');

            $table->decimal('ownership_percentage', 5, 2)->nullable(); // Solo para owners
            $table->boolean('is_primary')->default(false); // Principal responsable (si aplica)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_clients');
    }
};
