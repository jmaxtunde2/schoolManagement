<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->unsignedBigInteger('teacher_id')->nullable()->change();
            $table->foreign('teacher_id')->references('id')->on('teachers')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->unsignedBigInteger('teacher_id')->nullable(false)->change();
            $table->foreign('teacher_id')->references('id')->on('teachers')->restrictOnDelete();
        });
    }
};
