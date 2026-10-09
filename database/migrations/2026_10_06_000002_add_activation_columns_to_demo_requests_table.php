<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Élargit `demo_requests` à la partie « activation » du parcours
     * commercial : demande de paiement initial, confirmation manuelle,
     * puis école créée et compte administrateur provisionné.
     */
    public function up(): void
    {
        Schema::table('demo_requests', function (Blueprint $table) {
            $table->foreignId('school_id')
                ->nullable()
                ->constrained('schools')
                ->nullOnDelete();

            $table->timestamp('payment_requested_at')->nullable();
            $table->timestamp('payment_confirmed_at')->nullable();
            $table->foreignId('payment_confirmed_by')
                ->nullable()
                ->constrained('users');

            $table->timestamp('activated_at')->nullable();
            $table->foreignId('activated_by')
                ->nullable()
                ->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('demo_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
            $table->dropConstrainedForeignId('payment_confirmed_by');
            $table->dropConstrainedForeignId('activated_by');
            $table->dropColumn([
                'payment_requested_at',
                'payment_confirmed_at',
                'activated_at',
            ]);
        });
    }
};