<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_requests', function (Blueprint $table) {
            $table->id();

            // Identité de l'établissement interessé — formulaire public court.
            $table->string('school_name');
            $table->string('address');
            $table->string('phone', 40);
            $table->string('email');

            // Créneau de démonstration souhaité par l'école.
            $table->date('preferred_demo_date');
            $table->time('preferred_demo_time');

            // Workflow commercial : pending -> contacted -> scheduled -> demo_done -> approved.
            $table->string('status', 20)->default('pending')->index();

            // Traçabilité des étapes (null tant que l'étape n'est pas atteinte).
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('demo_done_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Qui traite la demande, et les notes réservées à l'équipe Coriyase.
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable();

            $table->timestamps();

            $table->index(['status', 'preferred_demo_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_requests');
    }
};
