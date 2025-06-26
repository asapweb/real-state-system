<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();

            // Relación polimórfica
            $table->morphs('attachable'); // attachable_type + attachable_id

            // Categoría del archivo
            $table->foreignId('attachment_category_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name'); // Nombre original
            $table->string('file_path'); // Ruta relativa o full
            $table->string('mime_type', 100)->nullable();
            $table->bigInteger('size')->nullable(); // En bytes

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
