<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ligne de paie : le salaire d'un membre du personnel pour un mois donné.
     *
     * Chaque feuille de paie mensuelle est rattachée à une dépense de catégorie
     * `salaire` (`expense_id`), ce qui permet de suivre montant total, approbation
     * et historique via la table `expenses`.
     */
    public function up(): void
    {
        Schema::create('salary_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();

            $table->date('salary_month');
            $table->unsignedInteger('amount');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['school_id', 'staff_id', 'salary_month']);
            $table->index(['school_id', 'salary_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_records');
    }
};