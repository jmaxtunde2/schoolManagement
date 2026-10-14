<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_batch_evaluation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluation_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['notification_batch_id', 'evaluation_id'],
                'notification_batch_evaluation_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_batch_evaluation');
    }
};
