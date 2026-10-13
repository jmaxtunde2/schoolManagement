<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Annonces libres envoyées aux parents (réunion, information, convocation…).
 * Distinct de la table `notifications` qui est couplée aux évaluations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title', 180);
            $table->text('body');

            // Canaux demandés par l'auteur : ["whatsapp", "email"].
            $table->json('channels');

            // Ciblage : all | classes | levels | guardians
            $table->string('audience_type', 20)->default('all');
            // Niveaux ciblés lorsque audience_type = levels (ex. ["6ème", "5ème"]).
            $table->json('levels')->nullable();

            // Pièce jointe optionnelle (stockée sur le disque « public »).
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_mime', 120)->nullable();
            $table->unsignedInteger('attachment_size')->nullable();

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            // draft | scheduled | sending | sent | partial | failed | cancelled
            $table->string('status', 20)->default('draft');

            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
