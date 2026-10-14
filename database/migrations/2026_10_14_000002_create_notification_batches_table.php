<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('notification_type', 40)->default('validated_evaluation');
            $table->date('notification_date');
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 20)->default('sms');
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('recipient_count')->default(0);
            $table->text('message');
            $table->unsignedInteger('cost')->nullable();
            $table->string('currency', 10)->nullable();
            $table->timestamps();

            $table->unique(
                ['school_id', 'student_id', 'notification_date', 'notification_type'],
                'notification_batches_unique'
            );
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_batches');
    }
};
