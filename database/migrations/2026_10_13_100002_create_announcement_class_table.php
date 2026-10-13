<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Classes ciblées par une annonce (audience_type = classes). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_class', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['announcement_id', 'class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_class');
    }
};
