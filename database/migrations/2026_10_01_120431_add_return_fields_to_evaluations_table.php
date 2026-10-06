<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->timestamp('returned_at')->nullable();

            $table->foreignId('returned_by')
                ->nullable()
                ->after('returned_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->text('return_reason')
                ->nullable()
                ->after('returned_by');
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropForeign(['returned_by']);

            $table->dropColumn([
                'returned_at',
                'returned_by',
                'return_reason',
            ]);
        });
    }
};
