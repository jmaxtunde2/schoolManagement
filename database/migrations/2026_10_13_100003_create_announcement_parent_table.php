<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Parents explicitement ciblés par une annonce (audience_type = guardians). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_parent', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->constrained('parents')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['announcement_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_parent');
    }
};
