<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Licences CoriSchool : le droit d'utilisation pour une période donnée.
     *
     * `academic_year_id` est laissé nullable : l'année scolaire correspondante
     * est créée en aval (onboarding / transition d'année). Cette colonne est
     * renseignée lorsqu'une année est liée à la licence.
     */
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type', 20);
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('XOF');
            $table->string('status', 30);

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};