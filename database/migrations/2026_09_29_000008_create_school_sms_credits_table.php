<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('school_sms_credits', function(Blueprint $table){ $table->id(); $table->foreignId('school_id')->constrained()->cascadeOnDelete(); $table->unsignedInteger('credits')->default(0); $table->string('source',50)->default('purchase'); $table->unsignedInteger('amount_paid')->nullable(); $table->timestamps(); $table->index(['school_id','created_at']); }); }
 public function down(): void { Schema::dropIfExists('school_sms_credits'); }
};
