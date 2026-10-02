<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('evaluations',function(Blueprint $t){$t->foreignId('academic_period_id')->nullable()->after('academic_year_id')->constrained('academic_periods')->nullOnDelete();}); } public function down(): void { Schema::table('evaluations',function(Blueprint $t){$t->dropConstrainedForeignId('academic_period_id');}); } };
