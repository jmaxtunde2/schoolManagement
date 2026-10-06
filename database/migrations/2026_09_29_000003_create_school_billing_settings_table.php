<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_billing_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('installation_fee')->default(30000);
            $table->unsignedInteger('annual_student_fee')->default(1000);
            $table->unsignedInteger('coriyase_share')->default(700);
            $table->unsignedInteger('school_share')->default(300);
            $table->unsignedSmallInteger('included_sms_per_paid_student')->default(6);
            $table->unsignedInteger('extra_sms_credit_unit')->default(100);
            $table->boolean('communication_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_billing_settings');
    }
};
