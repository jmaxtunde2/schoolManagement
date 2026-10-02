<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->unique()->constrained()->cascadeOnDelete();

            // Identité
            $table->string('school_name')->nullable();
            $table->string('short_name', 50)->nullable();
            $table->string('slogan')->nullable();
            $table->text('description')->nullable();
            $table->string('school_type', 30)->nullable();
            $table->unsignedSmallInteger('founded_year')->nullable();

            // Coordonnées
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('phone_secondary', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('contact_name')->nullable();

            // Identité visuelle
            $table->string('logo_path')->nullable();
            $table->string('primary_color', 7)->nullable();
            $table->string('secondary_color', 7)->nullable();
            $table->string('accent_color', 7)->nullable();

            // Réservé aux futurs paramètres sans migration lourde
            $table->json('options')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_settings');
    }
};
