<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timetables', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable()->after('teacher_id')->constrained('rooms')->nullOnDelete();
        });

        // Reprise des salles libres-textes existantes vers la table `rooms`.
        $legacy = DB::table('timetables')
            ->whereNotNull('room')
            ->select('school_id', 'room')
            ->distinct()
            ->get();

        foreach ($legacy as $row) {
            $roomId = DB::table('rooms')->where('school_id', $row->school_id)
                ->where('name', $row->room)
                ->value('id');

            if (! $roomId) {
                $roomId = DB::table('rooms')->insertGetId([
                    'school_id' => $row->school_id,
                    'name' => $row->room,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('timetables')
                ->where('school_id', $row->school_id)
                ->where('room', $row->room)
                ->update(['room_id' => $roomId]);
        }

        Schema::table('timetables', function (Blueprint $table) {
            $table->dropIndex('timetables_room_day_index');
            $table->dropColumn('room');
            $table->index(['school_id', 'room_id', 'day_of_week'], 'timetables_room_day_index');
        });
    }

    public function down(): void
    {
        Schema::table('timetables', function (Blueprint $table) {
            $table->string('room', 60)->nullable()->after('teacher_id');
        });

        // Restauration approximative du libellé texte depuis la salle liée.
        DB::table('timetables as t')
            ->join('rooms as r', 'r.id', '=', 't.room_id')
            ->update(['t.room' => DB::raw('r.name')]);

        Schema::table('timetables', function (Blueprint $table) {
            $table->dropIndex('timetables_room_day_index');
            $table->dropForeign(['room_id']);
            $table->dropColumn('room_id');
            $table->index(['school_id', 'room', 'day_of_week'], 'timetables_room_day_index');
        });
    }
};