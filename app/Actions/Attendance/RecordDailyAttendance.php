<?php

namespace App\Actions\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\ClassRoom;
use App\Models\School;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class RecordDailyAttendance
{
    public function __construct(private AuditService $audit)
    {
    }

    public function handle(
        School $school,
        ClassRoom $classRoom,
        string $date,
        ?int $periodId,
        array $rows,
        int $recorderId
    ): void {
        DB::transaction(function () use ($school, $classRoom, $date, $periodId, $rows, $recorderId) {
            foreach ($rows as $row) {
                $record = AttendanceRecord::withoutGlobalScopes()->firstOrNew([
                    'school_id' => $school->id,
                    'student_id' => $row['student_id'],
                    'attendance_date' => $date,
                ]);
                $existed = $record->exists;
                $before = $existed ? $record->only([
                    'class_room_id', 'status', 'delay_minutes', 'reason', 'note', 'justified_at', 'justified_by',
                ]) : null;
                $status = AttendanceStatus::from($row['status']);

                $record->fill([
                    'school_id' => $school->id,
                    'class_room_id' => $classRoom->id,
                    'academic_period_id' => $periodId,
                    'status' => $status,
                    'delay_minutes' => $status === AttendanceStatus::Late
                        ? (int) $row['delay_minutes']
                        : null,
                    'reason' => $row['reason'] ?? null,
                    'note' => $row['note'] ?? null,
                    'recorded_by' => $recorderId,
                ]);

                if ($status !== AttendanceStatus::Absent) {
                    $record->justified_at = null;
                    $record->justified_by = null;
                }

                $record->save();

                $this->audit->log(
                    $existed ? 'attendance.updated' : 'attendance.created',
                    $record,
                    [
                        'student_id' => $record->student_id,
                        'attendance_date' => $date,
                        'before' => $before,
                        'after' => $record->only([
                            'class_room_id', 'status', 'delay_minutes', 'reason', 'note', 'justified_at', 'justified_by',
                        ]),
                    ]
                );
            }
        });
    }
}