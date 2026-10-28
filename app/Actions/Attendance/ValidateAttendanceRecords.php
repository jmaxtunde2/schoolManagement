<?php

namespace App\Actions\Attendance;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceValidationStatus;
use App\Enums\CommunicationChannel;
use App\Jobs\SendCommunicationJob;
use App\Models\AttendanceRecord;
use App\Models\Notification;
use App\Models\ParentGuardian;
use App\Models\User;
use App\Services\AuditService;
use App\Services\Communication\CommunicationBudgetService;
use App\Services\Communication\NotificationChannelService;
use App\Services\UserNotifications\UserNotificationService;
use Illuminate\Support\Collection;

class ValidateAttendanceRecords
{
    public function __construct(
        private AuditService $audit,
        private UserNotificationService $notifications,
        private NotificationChannelService $channels,
        private CommunicationBudgetService $budget,
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

        if (in_array($record->status, [AttendanceStatus::Absent, AttendanceStatus::Late], true)) {
            $this->queueExternalNotifications($record, $studentName, $date);
        }

        return true;
    }

    private function queueExternalNotifications(AttendanceRecord $record, string $studentName, string $date): void
    {
        $school = $record->school;
        if (! $school) {
            return;
        }

        $type = $record->status === AttendanceStatus::Absent ? 'absence' : 'lateness';
        $parents = ParentGuardian::withoutGlobalScopes()
            ->where('school_id', $record->school_id)
            ->whereHas('students', fn ($students) => $students->whereKey($record->student_id))
            ->get();

        foreach ($parents as $parent) {
            $channel = $this->channels->select($school, $parent->phone, $parent->email);
            if (! $channel) {
                continue;
            }

            $metadata = null;
            if ($channel === CommunicationChannel::Whatsapp) {
                $metadata = ['whatsapp' => [
                    'template' => $record->status === AttendanceStatus::Absent ? 'corischool_absence' : 'template_retard',
                    'language' => config('services.whatsapp.lateness_template_language', 'fr'),
                    'body_parameters' => $record->status === AttendanceStatus::Absent
                        ? [$school->name, $parent->name, $studentName, $date]
                        : [$school->name, $parent->name, $studentName, $date, optional($record->recorded_at)->format('H:i') ?? now()->format('H:i')],
                    'url_parameter' => $school->slug,
                ]];
                if ($record->status === AttendanceStatus::Absent) {
                    $metadata['whatsapp']['language'] = 'fr';
                }
            }

            $key = hash('sha256', implode('|', ['attendance', $record->id, $parent->id, $type, $channel->value]));
            [$cost, $currency] = $this->budget->costFor($channel);
            $notification = Notification::withoutGlobalScopes()->firstOrCreate(
                ['idempotency_key' => $key],
                [
                    'school_id' => $record->school_id,
                    'notification_type' => $type,
                    'student_id' => $record->student_id,
                    'parent_id' => $parent->id,
                    'channel' => $channel->value,
                    'phone' => $channel === CommunicationChannel::Email ? null : $parent->phone,
                    'email' => $channel === CommunicationChannel::Email ? $parent->email : null,
                    'message' => $record->status === AttendanceStatus::Absent
                        ? "Votre enfant {$studentName} a été déclaré absent le {$date}."
                        : "Votre enfant {$studentName} a été déclaré en retard le {$date} à ".(optional($record->recorded_at)->format('H:i') ?? now()->format('H:i')).'.',
                    'metadata' => $metadata,
                    'status' => 'pending',
                    'estimated_cost' => $cost,
                    'cost_currency' => $currency,
                ]
            );

            if ($notification->wasRecentlyCreated) {
                SendCommunicationJob::dispatch($notification->id);
            }
        }
    }
}
