<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Actions de suivi enregistrées pour un élève en difficulté (phase 5).
 *
 * Chaque action trace une décision pédagogique concrète (entretien, remise à
 * niveau, convocation des parents…). Ces fiches sont un outil de pilotage
 * scolaire et n'ont aucune portée médicale ou psychologique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // academic | attendance | behaviour | other
            $table->string('type', 20)->default('academic');
            $table->string('title', 180);
            $table->text('notes')->nullable();

            // planned | in_progress | done
            $table->string('status', 20)->default('planned');
            $table->date('due_date')->nullable();

            $table->timestamps();

            $table->index(['school_id', 'student_id']);
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_follow_ups');
    }
};
