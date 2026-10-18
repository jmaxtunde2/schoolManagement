<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_resources', function (Blueprint $table) {
            $table->id();

            // Nul pour une ressource de la plateforme (banque centrale),
            // renseigné pour une ressource propre à un établissement.
            $table->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete();

            $table->foreignId('platform_level_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('platform_subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('type', 30);
            $table->string('difficulty', 20)->default('medium');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('academic_year', 20)->nullable();
            $table->string('period', 30)->nullable();
            $table->longText('content')->nullable();
            $table->boolean('is_free')->default(true);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('downloads_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'school_id']);
            $table->index('type');
            $table->index('difficulty');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_resources');
    }
};
