<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('school_mail_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('mailer', 30)->default('smtp');

            $table->string('host');
            $table->unsignedSmallInteger('port')->default(587);

            $table->string('username')->nullable();
            $table->text('password')->nullable();

            $table->string('encryption', 20)->nullable();

            $table->string('from_address');
            $table->string('from_name');

            $table->string('reply_to')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_mail_settings');
    }
};
