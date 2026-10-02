<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('student_contributions', function(Blueprint $table){ $table->id(); $table->foreignId('school_id')->constrained()->cascadeOnDelete(); $table->foreignId('student_id')->constrained()->cascadeOnDelete(); $table->foreignId('parent_id')->nullable()->constrained('parents')->nullOnDelete(); $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete(); $table->unsignedInteger('amount_due')->default(1000); $table->unsignedInteger('amount_paid')->default(0); $table->string('status',20)->default('pending'); $table->timestamp('paid_at')->nullable(); $table->timestamps(); $table->unique(['student_id','academic_year_id']); $table->index(['school_id','status']); }); }
 public function down(): void { Schema::dropIfExists('student_contributions'); }
};
