<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_resource_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_resource_id')->constrained()->cascadeOnDelete();

            // document = fichier joint de l'épreuve ; answer_key = corrigé protégé.
            $table->string('kind', 20)->default('document');
            $table->string('disk', 30)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->boolean('watermarked')->default(false);
            $table->timestamps();

            $table->index(['exam_resource_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_resource_attachments');
    }
};
