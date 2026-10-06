<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_cards', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->foreignId('academic_period_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('version')->default(1);
            $t->decimal('general_average', 5, 2)->nullable();
            $t->unsignedInteger('rank')->nullable();
            $t->text('appreciation')->nullable();
            $t->string('verification_token', 64)->unique();
            $t->timestamp('published_at')->nullable();
            $t->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['student_id', 'academic_period_id', 'version']);
        });
        Schema::create('report_card_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_card_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subject_id')->constrained()->restrictOnDelete();
            $t->decimal('coefficient', 4, 1)->default(1);
            $t->decimal('average', 5, 2)->nullable();
            $t->text('appreciation')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_card_items');
        Schema::dropIfExists('report_cards');
    }
};
