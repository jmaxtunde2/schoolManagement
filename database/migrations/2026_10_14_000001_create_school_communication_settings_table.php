<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_communication_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->unique()->constrained()->cascadeOnDelete();

            $table->boolean('sms_enabled')->default(true);
            $table->unsignedInteger('sms_budget')->default(3000);
            $table->unsignedInteger('sms_balance')->default(3000);
            $table->unsignedInteger('sms_low_balance_threshold')->default(500);
            $table->unsignedInteger('sms_reactivation_threshold')->default(1000);

            $table->boolean('whatsapp_enabled')->default(true);
            $table->decimal('whatsapp_budget', 8, 2)->default(5);
            $table->decimal('whatsapp_consumed', 8, 2)->default(0);
            $table->decimal('whatsapp_low_budget_threshold', 8, 2)->default(1);

            $table->boolean('email_enabled')->default(true);

            $table->timestamp('last_sms_balance_check_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_communication_settings');
    }
};
