<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('index_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();     // Ej: 'ipc', 'uva', 'ripte'
            $table->string('name');               // Ej: 'IPC INDEC', 'UVA BCRA'
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('index_types');
    }
};
