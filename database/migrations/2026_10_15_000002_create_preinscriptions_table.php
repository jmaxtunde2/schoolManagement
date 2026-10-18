<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demandes de préinscription en ligne (phase 7).
 *
 * Le parent dépose une demande depuis le mini-site de l'établissement ; une
 * fois validée par l'administration, un élève est automatiquement créé dans
 * CoriSchool et affecté à une classe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preinscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();

            // Identité de l'élève à inscrire
            $table->string('first_name', 120);
            $table->string('last_name', 120);
            $table->date('birth_date')->nullable();
            $table->string('gender', 1)->nullable();

            // Niveau souhaité (la classe est affectée à la validation)
            $table->string('requested_level', 40)->nullable();

            // Coordonnées du parent / responsable
            $table->string('parent_name', 160);
            $table->string('parent_phone', 40);
            $table->string('parent_email')->nullable();
            $table->string('parent_relationship', 40)->nullable();

            $table->text('notes')->nullable();

            // Chemins des pièces jointes (liste JSON sur le disque public)
            $table->json('documents')->nullable();

            // pending | approved | rejected
            $table->string('status', 20)->default('pending');

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();

            $table->timestamps();

            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preinscriptions');
    }
};
