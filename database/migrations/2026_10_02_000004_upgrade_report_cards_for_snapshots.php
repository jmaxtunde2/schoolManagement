<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_cards', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->dropForeign(['academic_period_id']);
            $table->foreign('academic_period_id')->references('id')->on('academic_periods')->restrictOnDelete();
            $table->foreignId('academic_year_id')->nullable()->after('academic_period_id')->constrained()->nullOnDelete();
            $table->foreignId('class_room_id')->nullable()->after('student_id')->constrained('classes')->nullOnDelete();
            $table->string('status', 20)->default('draft')->after('version');
            $table->unsignedInteger('total_students')->nullable()->after('rank');
            $table->json('attendance_summary')->nullable()->after('appreciation');
            $table->json('snapshot')->nullable()->after('attendance_summary');
            $table->timestamp('generated_at')->nullable()->after('snapshot');
            $table->foreignId('generated_by')->nullable()->after('generated_at')->constrained('users')->nullOnDelete();
            $table->string('pdf_path')->nullable()->after('verification_token');
            $table->timestamp('pdf_generated_at')->nullable()->after('pdf_path');
            $table->index(['school_id', 'academic_year_id', 'academic_period_id', 'class_room_id', 'status'], 'report_cards_academic_context_idx');
        });

        Schema::table('report_card_items', function (Blueprint $table) {
            $table->string('subject_name')->nullable()->after('subject_id');
            $table->unsignedSmallInteger('evaluation_count')->default(0)->after('average');
            $table->unsignedInteger('rank')->nullable()->after('evaluation_count');
            $table->text('teacher_comment')->nullable()->after('appreciation');
        });
    }

    public function down(): void
    {
        Schema::table('report_card_items', function (Blueprint $table) {
            $table->dropColumn(['subject_name', 'evaluation_count', 'rank', 'teacher_comment']);
        });

        Schema::table('report_cards', function (Blueprint $table) {
            $table->dropIndex('report_cards_academic_context_idx');
            $table->dropConstrainedForeignId('academic_year_id');
            $table->dropConstrainedForeignId('class_room_id');
            $table->dropConstrainedForeignId('generated_by');
            $table->dropForeign(['student_id']);
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->dropForeign(['academic_period_id']);
            $table->foreign('academic_period_id')->references('id')->on('academic_periods')->cascadeOnDelete();
            $table->dropColumn([
                'status', 'total_students', 'attendance_summary', 'snapshot',
                'generated_at', 'pdf_path', 'pdf_generated_at',
            ]);
        });
    }
};
