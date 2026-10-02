<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('teacher')->after('password');
            $table->boolean('is_active')->default(true)->after('role');
            $table->index(['school_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['school_id', 'role']);
            $table->dropConstrainedForeignId('school_id');
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
