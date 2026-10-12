<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Dernier pas de temps TOTP accepté (protection anti-rejeu :
            // un code déjà consommé ne peut pas être réutilisé dans sa
            // fenêtre de validité).
            $table->unsignedBigInteger('two_factor_last_counter')
                ->nullable()
                ->after('two_factor_last_used_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('two_factor_last_counter');
        });
    }
};
