<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 1 = lundi ... 6 = samedi
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('room', 60)->nullable();
            $table->timestamps();

            // Lecture hebdomadaire : une classe pour un jour donné.
            $table->index(['school_id', 'academic_year_id', 'class_id', 'day_of_week', 'starts_at'], 'timetables_weekly_class_index');
            // Contrôle des collisions côté serveur (un enseignant, une classe ou une salle n'est pas libre deux fois).
            $table->index(['school_id', 'academic_year_id', 'teacher_id', 'day_of_week'], 'timetables_teacher_day_index');
            $table->index(['school_id', 'room', 'day_of_week'], 'timetables_room_day_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetables');
    }
};
