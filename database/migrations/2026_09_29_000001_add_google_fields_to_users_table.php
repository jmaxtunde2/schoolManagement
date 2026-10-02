<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('users', function(Blueprint $table){ $table->string('google_id')->nullable()->unique()->after('email'); $table->string('google_email')->nullable()->after('google_id'); $table->string('google_avatar')->nullable()->after('google_email'); $table->timestamp('google_verified_at')->nullable()->after('google_avatar'); $table->timestamp('google_reauthenticated_at')->nullable()->after('google_verified_at'); $table->index(['school_id','is_active']); }); }
 public function down(): void { Schema::table('users', function(Blueprint $table){ $table->dropIndex(['school_id','is_active']); $table->dropUnique(['google_id']); $table->dropColumn(['google_id','google_email','google_avatar','google_verified_at','google_reauthenticated_at']); }); }
};
