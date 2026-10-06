<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('two_factor_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('event', 50);
            $t->ipAddress('ip_address')->nullable();
            $t->text('user_agent')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('two_factor_events');
    }
};
