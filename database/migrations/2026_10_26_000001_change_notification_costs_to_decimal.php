<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->decimal('estimated_cost', 12, 6)->nullable()->change();
        });

        Schema::table('notification_batches', function (Blueprint $table) {
            $table->decimal('cost', 12, 6)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->unsignedInteger('estimated_cost')->nullable()->change();
        });

        Schema::table('notification_batches', function (Blueprint $table) {
            $table->unsignedInteger('cost')->nullable()->change();
        });
    }
};
