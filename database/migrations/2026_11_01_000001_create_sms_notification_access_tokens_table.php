<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_notification_access_tokens', function (Blueprint $table) {
            $table->id();

            // Le token brut n'est jamais stocké : seul son hash SHA-256 l'est.
            $table->string('token_hash', 64)->unique();

            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->constrained('parents')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            // Renseigné lorsqu'un SMS concerne une seule évaluation. Pour un lot
            // consolidé (plusieurs évaluations le même jour), la page s'appuie
            // sur notification_batch_id.
            $table->foreignId('evaluation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('notification_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('notification_batch_id')
                ->nullable()
                ->constrained('notification_batches')
                ->nullOnDelete();

            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->unsignedInteger('use_count')->default(0);
            $table->unsignedInteger('max_uses')->default(25);

            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason')->nullable();

            $table->string('last_ip', 45)->nullable();
            $table->string('last_user_agent', 500)->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['school_id', 'student_id']);
            $table->index(['school_id', 'parent_id']);
            $table->index('expires_at');
            $table->index('revoked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_notification_access_tokens');
    }
};
