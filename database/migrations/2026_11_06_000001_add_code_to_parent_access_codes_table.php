<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_access_codes', function (Blueprint $table) {
            // Code en clair chiffré (cast "encrypted"), afin de pouvoir le
            // réafficher/imprimer après sa génération. Le hash reste utilisé
            // pour la vérification lors de la connexion.
            $table->text('code')->nullable()->after('code_hash');
        });
    }

    public function down(): void
    {
        Schema::table('parent_access_codes', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
