<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('evaluation_id')->nullable()->change();

            $table->foreignId('notification_batch_id')
                ->nullable()
                ->after('evaluation_id')
                ->constrained('notification_batches')
                ->nullOnDelete();

            $table->string('notification_type', 40)
                ->nullable()
                ->after('notification_batch_id');

            $table->index(['notification_batch_id', 'status'], 'notifications_batch_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_batch_status_index');
            $table->dropConstrainedForeignId('notification_batch_id');
            $table->dropColumn('notification_type');
            $table->unsignedBigInteger('evaluation_id')->nullable(false)->change();
        });
    }
};
