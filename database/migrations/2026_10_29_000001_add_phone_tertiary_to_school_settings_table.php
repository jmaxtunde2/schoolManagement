<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('school_settings', 'phone_tertiary')) {
            return;
        }

        Schema::table('school_settings', function (Blueprint $table) {
            $table->string('phone_tertiary', 30)->nullable()->after('phone_secondary');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('school_settings', 'phone_tertiary')) {
            return;
        }

        Schema::table('school_settings', function (Blueprint $table) {
            $table->dropColumn('phone_tertiary');
        });
    }
};
