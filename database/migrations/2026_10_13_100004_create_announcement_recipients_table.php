<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un destinataire = un parent × un canal pour une annonce donnée.
 * Suit l'envoi (statut, provider, erreur) indépendamment des `notifications`
 * (couplées aux évaluations).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->constrained('parents')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();

            $table->string('channel', 20);
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('message');

            $table->string('status', 20)->default('pending');
            $table->string('provider', 40)->nullable();
            $table->string('external_id')->nullable();
            $table->text('error')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('idempotency_key', 191)->nullable()->unique();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'channel', 'status']);
            $table->index(['announcement_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_recipients');
    }
};
