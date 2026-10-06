<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('channel', 20)->default('sms')->after('parent_id');
            $table->string('email')->nullable()->after('phone');
            $table->string('idempotency_key', 191)->nullable()->unique()->after('status');
            $table->unsignedInteger('estimated_cost')->nullable()->after('attempts');
            $table->string('cost_currency', 10)->nullable()->after('estimated_cost');
            $table->index(['school_id', 'channel', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['school_id', 'channel', 'status']);
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn(['channel', 'email', 'idempotency_key', 'estimated_cost', 'cost_currency']);
        });
    }
};
