<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->string('validation_status', 20)->default('pending')->after('status');
            $table->timestamp('validated_at')->nullable()->after('justified_by');
            $table->foreignId('validated_by')->nullable()->constrained('users');
        });

        // L'historique existant était déjà effectif : on le considère validé.
        DB::table('attendance_records')->update(['validation_status' => 'validated']);
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('validated_by');
            $table->dropColumn(['validation_status', 'validated_at']);
        });
    }
};
