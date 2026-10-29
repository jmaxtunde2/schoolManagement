<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('school_settings', 'founded_date')) {
            return;
        }

        Schema::table('school_settings', function (Blueprint $table) {
            $table->date('founded_date')->nullable()->after('school_type');
        });

        if (Schema::hasColumn('school_settings', 'founded_year')) {
            DB::table('school_settings')
                ->whereNotNull('founded_year')
                ->orderBy('id')
                ->chunkById(100, function ($settings) {
                    foreach ($settings as $setting) {
                        DB::table('school_settings')
                            ->where('id', $setting->id)
                            ->update(['founded_date' => sprintf('%04d-01-01', $setting->founded_year)]);
                    }
                });

            Schema::table('school_settings', function (Blueprint $table) {
                $table->dropColumn('founded_year');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('school_settings', 'founded_date')) {
            return;
        }

        Schema::table('school_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('founded_year')->nullable()->after('school_type');
        });

        DB::table('school_settings')
            ->whereNotNull('founded_date')
            ->orderBy('id')
            ->chunkById(100, function ($settings) {
                foreach ($settings as $setting) {
                    DB::table('school_settings')
                        ->where('id', $setting->id)
                        ->update(['founded_year' => (int) substr($setting->founded_date, 0, 4)]);
                }
            });

        Schema::table('school_settings', function (Blueprint $table) {
            $table->dropColumn('founded_date');
        });
    }
};
