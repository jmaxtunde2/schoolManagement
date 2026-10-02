<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->constrained('parents')->cascadeOnDelete();

            $table->string('phone', 30);
            $table->text('message');
            $table->string('provider', 40)->nullable();
            $table->string('status', 20)->default('pending'); // pending, queued, sent, delivered, failed
            $table->string('external_id')->nullable();
            $table->text('error')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'evaluation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
