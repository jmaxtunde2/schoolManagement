<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->foreignId('entered_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->after('entered_by')->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('validated_at');
            $table->index(['school_id', 'status', 'submitted_at']);
        });
        DB::table('evaluations')->where('status', 'ready')->update(['status' => 'in_progress']);
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropIndex(['school_id', 'status', 'submitted_at']);
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropConstrainedForeignId('entered_by');
            $table->dropColumn('submitted_at');
        });
        DB::table('evaluations')->where('status', 'in_progress')->update(['status' => 'ready']);
    }
};
