<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dépenses de l'établissement.
     *
     * Toute dépense dont le montant dépasse le seuil configuré (par défaut 50 000 F)
     * doit être approuvée par l'administrateur. La feuille de paie mensuelle est
     * elle aussi matérialisée par une dépense de catégorie `salaire`, dont les
     * détails par membre sont portés par la table `salary_records`.
     */
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();

            $table->string('category', 30);
            $table->string('reference', 60)->nullable();
            $table->string('label', 255);
            $table->unsignedInteger('amount');
            $table->date('expense_date');
            $table->string('status', 20)->default('pending');
            $table->string('justification', 500)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();

            $table->timestamps();

            $table->index(['school_id', 'expense_date']);
            $table->index(['school_id', 'status']);
            $table->unique(['school_id', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};