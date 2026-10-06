<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('parents', 'phone_secondary')) {
            return;
        }

        Schema::table('parents', function (Blueprint $table) {
            // Second numéro du responsable : `phone` reste le numéro principal.
            // Aucune bascule automatique SMS n'est introduite ici.
            $table->string('phone_secondary', 30)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('parents', 'phone_secondary')) {
            return;
        }

        Schema::table('parents', function (Blueprint $table) {
            $table->dropColumn('phone_secondary');
        });
    }
};
