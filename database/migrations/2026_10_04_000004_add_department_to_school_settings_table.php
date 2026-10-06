<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('school_settings', 'department')) {
            return;
        }

        Schema::table('school_settings', function (Blueprint $table) {
            // Département Béninien, nécessaire pour l'inscription d'une école
            // (Cotonou et Porto-Novo ne suffisent pas à situer un établissement).
            $table->string('department', 60)->nullable()->after('city');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('school_settings', 'department')) {
            return;
        }

        Schema::table('school_settings', function (Blueprint $table) {
            $table->dropColumn('department');
        });
    }
};
