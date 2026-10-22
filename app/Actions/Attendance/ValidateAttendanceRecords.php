<?php

namespace App\Actions\Attendance;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceValidationStatus;
use App\Models\AttendanceRecord;
use App\Models\ParentGuardian;
use App\Models\User;
use App\Services\AuditService;
use App\Services\UserNotifications\UserNotificationService;
use Illuminate\Support\Collection;

class ValidateAttendanceRecords
{
    public function __construct(
        private AuditService $audit,
        private UserNotificationService $notifications,
    ) {}

    /**
     * Valide des relevés d'assiduité et notifie les parents des élèves
     * concernés par un incident (absence, retard, permission).
     *
     * @param  Collection<int, AttendanceRecord>|array<int, AttendanceRecord>  $records
     * @return array{validated: int, notified: int}
     */
    public function handle(Collection|array $records, User $validator): array
    {
        $validated = 0;
        $notified = 0;

        foreach ($records as $record) {
            if ($record->validation_status === AttendanceValidationStatus::Validated) {
                continue;
            }

            $record->update([
                'validation_status' => AttendanceValidationStatus::Validated,
                'validated_at' => now(),
                'validated_by' => $validator->id,
            ]);

            $this->audit->log('attendance.validated', $record, [
                'student_id' => $record->student_id,
                'attendance_date' => $record->attendance_date->toDateString(),
                'status' => $record->status->value,
            ]);

            $validated++;

            if ($this->notifyGuardians($record, $validator)) {
                $notified++;
            }
        }

        return ['validated' => $validated, 'notified' => $notified];
    }

    private function notifyGuardians(AttendanceRecord $record, User $validator): bool
    {
        if ($record->status === AttendanceStatus::Present) {
            return false;
        }

        $guardianUsers = ParentGuardian::withoutGlobalScopes()
            ->where('school_id', $record->school_id)
            ->whereHas('students', fn ($students) => $students->whereKey($record->student_id))
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter();

        if ($guardianUsers->isEmpty()) {
            return false;
        }

        $studentName = $record->student?->full_name ?? 'votre enfant';
        $date = $record->attendance_date->isoFormat('dddd D MMMM YYYY');

        [$title, $message] = match ($record->status) {
            AttendanceStatus::Absent => [
                'Absence signalée',
                "Votre enfant {$studentName} a été déclaré absent le {$date}. L'assiduité a été validée par {$validator->name}.",
            ],
            AttendanceStatus::Late => [
                'Retard signalé',
                "Votre enfant {$studentName} a été déclaré en retard de {$record->delay_minutes} min le {$date}. L'assiduité a été validée par {$validator->name}.",
            ],
            AttendanceStatus::Permission => [
                'Permission enregistrée',
                "Votre enfant {$studentName} est en permission le {$date}"
                    .($record->reason ? " (motif : {$record->reason})" : '')
                    .". L'assiduité a été validée par {$validator->name}.",
            ],
            default => [null, null],
        };

        if ($title === null) {
            return false;
        }

        foreach ($guardianUsers as $guardianUser) {
            $this->notifications->send(
                user: $guardianUser,
                type: 'attendance.validated',
                title: $title,
                message: $message,
                data: [
                    'attendance_record_id' => $record->id,
                    'student_id' => $record->student_id,
                    'status' => $record->status->value,
                    'url' => route('parent.children.attendance', $record->student_id),
                ],
            );
        }

        return true;
    }
}
