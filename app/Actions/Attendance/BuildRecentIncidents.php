<?php

namespace App\Actions\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use Illuminate\Support\Collection;

/**
 * Derniers cas de retard et d'absence à afficher sur les tableaux de bord.
 *
 * Le périmètre multi-école est garanti par SchoolScope (BelongsToSchool) : sans
 * filtre explicite, seuls les relevés de l'établissement courant sont remontés.
 */
class BuildRecentIncidents
{
    public function handle(int $limit = 10): array
    {
        $records = AttendanceRecord::query()
            ->with([
                'student:id,first_name,last_name,matricule',
                'classRoom:id,name',
            ])
            ->whereIn('status', [
                AttendanceStatus::Late->value,
                AttendanceStatus::Absent->value,
            ])
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return [
            'count' => $records->count(),
            'late_count' => $records->where('status', AttendanceStatus::Late)->count(),
            'absent_count' => $records->where('status', AttendanceStatus::Absent)->count(),
            'items' => $records->map(fn (AttendanceRecord $record) => [
                'id' => $record->id,
                'attendance_date' => $record->attendance_date->format('Y-m-d'),
                'status' => $record->status->value,
                'delay_minutes' => $record->delay_minutes,
                'reason' => $record->reason,
                'student' => [
                    'id' => $record->student?->id,
                    'name' => $record->student?->full_name,
                    'matricule' => $record->student?->matricule,
                ],
                'class_name' => $record->classRoom?->name,
            ])->all(),
        ];
    }
}
