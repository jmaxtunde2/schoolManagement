<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->string('notification_status', 30)
                ->nullable()
                ->after('return_reason');

            $table->timestamp('notified_at')
                ->nullable()
                ->after('notification_status');

            $table->index(['school_id', 'notification_status'], 'evaluations_notification_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropIndex('evaluations_notification_status_index');
            $table->dropColumn(['notification_status', 'notified_at']);
        });
    }
};
