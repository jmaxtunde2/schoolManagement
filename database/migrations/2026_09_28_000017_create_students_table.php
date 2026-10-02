<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->string('matricule', 40)->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date')->nullable();
            $table->string('gender', 1)->nullable(); // M / F
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['school_id', 'class_id']);
            $table->unique(['school_id', 'matricule']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
