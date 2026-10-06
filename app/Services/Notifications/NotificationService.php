<?php

namespace App\Services\Notifications;

use App\Enums\NotificationStatus;
use App\Jobs\SendEmailNotificationJob;
use App\Jobs\SendSmsNotificationJob;
use App\Models\Evaluation;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Notify parents/guardians after an evaluation is validated.
     *
     * @return int Number of notifications created.
     */
    public function notifyForEvaluation(Evaluation $evaluation): int
    {
        $this->loadEvaluationRelations($evaluation);

        $created = 0;

        DB::transaction(function () use ($evaluation, &$created) {
            $smsLimit = $this->getSmsLimit($evaluation);

            foreach ($evaluation->results as $result) {
                $guardians = $result->student->guardians;

                foreach ($guardians as $guardian) {
                    $message = $this->buildEvaluationMessage(
                        $evaluation,
                        $result
                    );

                    /*
                     * SMS
                     */
                    if ($guardian->phone) {
                        $smsCount = $this->getStudentSmsCount(
                            $evaluation,
                            $result->student_id
                        );

                        if ($smsCount < $smsLimit) {
                            $created += $this->createNotification(
                                evaluation: $evaluation,
                                result: $result,
                                guardian: $guardian,
                                channel: 'sms',
                                phone: $guardian->phone,
                                email: null,
                                message: $message
                            );
                        }
                    }

                    /*
                     * Email
                     */
                    if ($guardian->email) {
                        $created += $this->createNotification(
                            evaluation: $evaluation,
                            result: $result,
                            guardian: $guardian,
                            channel: 'email',
                            phone: null,
                            email: $guardian->email,
                            message: $message
                        );
                    }
                }
            }
        });

        return $created;
    }

    /**
     * Load all relationships required to build parent notifications.
     */
    private function loadEvaluationRelations(Evaluation $evaluation): void
    {
        $evaluation->loadMissing([
            'classRoom',
            'subject',
            'school',
            'results.student.guardians',
        ]);
    }

    /**
     * Get the SMS quota configured for the school.
     */
    private function getSmsLimit(Evaluation $evaluation): int
    {
        $settings = $evaluation
            ->school
            ->billingSettings()
            ->withoutGlobalScopes()
            ->first();

        return (int) (
            $settings?->included_sms_per_paid_student ?? 6
        );
    }

    /**
     * Count SMS notifications already created for a student
     * during the current academic year.
     */
    private function getStudentSmsCount(
        Evaluation $evaluation,
        int $studentId
    ): int {
        return Notification::withoutGlobalScopes()
            ->where('student_id', $studentId)
            ->where('channel', 'sms')
            ->whereHas(
                'evaluation',
                fn ($query) => $query->where(
                    'academic_year_id',
                    $evaluation->academic_year_id
                )
            )
            ->count();
    }

    /**
     * Create a notification if it does not already exist.
     */
    private function createNotification(
        Evaluation $evaluation,
        $result,
        $guardian,
        string $channel,
        ?string $phone,
        ?string $email,
        string $message
    ): int {
        $idempotencyKey = $this->buildIdempotencyKey(
            $evaluation,
            $result,
            $guardian,
            $channel
        );

        $notification = Notification::withoutGlobalScopes()
            ->firstOrCreate(
                [
                    'idempotency_key' => $idempotencyKey,
                ],
                [
                    'school_id' => $evaluation->school_id,
                    'evaluation_id' => $evaluation->id,
                    'student_id' => $result->student_id,
                    'parent_id' => $guardian->id,
                    'channel' => $channel,
                    'phone' => $phone,
                    'email' => $email,
                    'message' => $message,
                    'status' => 'pending',
                ]
            );

        if (! $notification->wasRecentlyCreated) {
            return 0;
        }

        $notification->recordStatus(
            NotificationStatus::Pending,
            'Notification créée après validation.'
        );

        $this->dispatchNotificationJob(
            $notification,
            $channel
        );

        return 1;
    }

    /**
     * Build the idempotency key used to prevent duplicates.
     */
    private function buildIdempotencyKey(
        Evaluation $evaluation,
        $result,
        $guardian,
        string $channel
    ): string {
        return hash(
            'sha256',
            implode('|', [
                $evaluation->id,
                $result->student_id,
                $guardian->id,
                $channel,
            ])
        );
    }

    /**
     * Dispatch the appropriate notification job.
     */
    private function dispatchNotificationJob(
        Notification $notification,
        string $channel
    ): void {
        if ($channel === 'sms') {
            SendSmsNotificationJob::dispatch(
                $notification->id
            );

            return;
        }

        SendEmailNotificationJob::dispatch(
            $notification->id
        );
    }

    /**
     * Build the message sent to the student's guardian.
     */
    private function buildEvaluationMessage(
        Evaluation $evaluation,
        $result
    ): string {
        $student = $result->student;

        $score = $result->is_absent
            ? 'Absent(e)'
            : number_format(
                (float) $result->score,
                1
            ).'/'.number_format(
                (float) $evaluation->max_score,
                0
            );

        return sprintf(
            '%s : note de %s en %s (%s) : %s.',
            $evaluation->school->name,
            $student->full_name,
            $evaluation->subject->name,
            $evaluation->title,
            $score
        );
    }
}
