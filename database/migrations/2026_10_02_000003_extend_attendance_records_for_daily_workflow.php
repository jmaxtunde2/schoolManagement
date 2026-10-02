<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreignId('class_room_id')->nullable()->after('student_id')->constrained('classes')->nullOnDelete();
            $table->string('reason', 500)->nullable()->after('delay_minutes');
            $table->timestamp('justified_at')->nullable()->after('recorded_by');
            $table->foreignId('justified_by')->nullable()->after('justified_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('justified_by');
            $table->dropConstrainedForeignId('class_room_id');
            $table->dropForeign(['student_id']);
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->dropColumn(['reason', 'justified_at']);
        });
    }
};