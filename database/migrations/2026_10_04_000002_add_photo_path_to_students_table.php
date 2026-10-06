<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('students', 'photo_path')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            // Chemin du fichier sur le disque `public`. Jamais l'image en Base64 :
            // l'URL est exposée via l'accesseur Student::photo_url.
            $table->string('photo_path')->nullable()->after('gender');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('students', 'photo_path')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};
