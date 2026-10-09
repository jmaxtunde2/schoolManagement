<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Paiements CoriSchool : revenus première année (initial) et
     * renouvellements. La confirmation est aujourd'hui manuelle (le Super
     * Admin atteste la réception), mais l'entité est prête pour les
     * providers futurs (Mobile Money, carte, virement…).
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('license_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('XOF');
            $table->string('status', 30);
            $table->string('method', 40)->default('manual');

            $table->string('reference', 100)->unique();
            $table->json('metadata')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};