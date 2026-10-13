<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contribution_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_contribution_id')->constrained('student_contributions')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('parents')->nullOnDelete();
            $table->unsignedInteger('amount');
            $table->string('method', 30)->default('cash');
            $table->string('reference', 60)->nullable();
            $table->string('receipt_number', 40)->nullable();
            $table->date('paid_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'paid_at']);
            $table->index(['student_contribution_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contribution_payments');
    }
};
