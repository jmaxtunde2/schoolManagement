<?php

namespace App\Services\Communication;

use App\Enums\CommunicationChannel;
use App\Enums\EvaluationNotificationStatus;
use App\Enums\EvaluationStatus;
use App\Enums\NotificationStatus;
use App\Jobs\SendCommunicationJob;
use App\Models\Evaluation;
use App\Models\Notification;
use App\Models\NotificationBatch;
use App\Models\School;
use App\Models\Student;
use App\Services\UserNotifications\UserNotificationService;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Consolide les évaluations validées en une notification par élève,
 * puis met les destinataires externes en file d'envoi par lots.
 */
class NotificationAggregationService
{
    public function __construct(
        private readonly CommunicationBudgetService $budget,
        private readonly NotificationChannelService $channelService,
        private readonly NotificationMessageBuilder $messages,
        private readonly UserNotificationService $userNotifications,
    ) {}

    /**
     * @return array{batches: int, recipients: int, queued: int, skipped: int}
     */
    public function aggregate(?CarbonInterface $now = null): array
    {
        $now = $now ?? now();

        $evaluations = Evaluation::withoutGlobalScopes()
            ->where('status', EvaluationStatus::Validated->value)
            ->whereNotNull('validated_at')
            ->where('validated_at', '<=', $now)
            ->where(fn ($query) => $query
                ->whereNull('notification_status')
                ->orWhere('notification_status', EvaluationNotificationStatus::Pending->value))
            ->whereHas('school', fn ($query) => $query->where('is_active', true))
            ->with(['school', 'subject', 'results.student.guardians.user'])
            ->get();

        $groups = $this->group($evaluations);

        $stats = ['batches' => 0, 'recipients' => 0, 'queued' => 0, 'skipped' => 0];
        $recipientIds = [];

        foreach ($groups as $group) {
            $processed = $this->processGroup($group, $now->toDateString());

            if ($processed === null) {
                $stats['skipped']++;

                continue;
            }

            $stats['batches']++;
            $stats['recipients'] += $processed['recipient_count'];
            $recipientIds = array_merge($recipientIds, $processed['recipient_ids']);
        }

        $this->queueRecipients($recipientIds);

        $stats['queued'] = count($recipientIds);

        return $stats;
    }

    /**
     * @param  Collection<int, Evaluation>  $evaluations
     * @return array<string, array{school: School, student: Student, entries: array, evaluations: array<int, Evaluation>}>
     */
    private function group($evaluations): array
    {
        $groups = [];

        foreach ($evaluations as $evaluation) {
            $school = $evaluation->school;

            if (! $school) {
                continue;
            }

            foreach ($evaluation->results as $result) {
                $student = $result->student;

                if (! $student) {
                    continue;
                }

                $key = $school->id.':'.$student->id;

                $groups[$key] ??= [
                    'school' => $school,
                    'student' => $student,
                    'entries' => [],
                    'evaluations' => [],
                ];

                $groups[$key]['entries'][] = [
                    'subject' => $evaluation->subject?->name ?? 'Matière',
                    'title' => (string) $evaluation->title,
                    'score' => $result->is_absent || $result->score === null ? null : (float) $result->score,
                    'max' => (float) $evaluation->max_score,
                    'absent' => (bool) $result->is_absent,
                ];

                $groups[$key]['evaluations'][$evaluation->id] = $evaluation;
            }
        }

        return $groups;
    }

    /**
     * @param  array{school: School, student: Student, entries: array, evaluations: array<int, Evaluation>}  $group
     * @return array{recipient_count: int, recipient_ids: array<int, int>}|null
     */
    private function processGroup(array $group, string $date): ?array
    {
        $school = $group['school'];
        $student = $group['student'];
        $entries = $group['entries'];
        $evaluationIds = array_keys($group['evaluations']);

        return DB::transaction(function () use ($school, $student, $entries, $evaluationIds, $date) {
            $batch = NotificationBatch::withoutGlobalScopes()->firstOrCreate(
                [
                    'school_id' => $school->id,
                    'student_id' => $student->id,
                    'notification_date' => $date,
                    'notification_type' => 'validated_evaluation',
                ],
                [
                    'channel' => CommunicationChannel::InApp->value,
                    'status' => NotificationStatus::Pending->value,
                    'recipient_count' => 0,
                    'message' => '',
                ]
            );

            if (! $batch->wasRecentlyCreated && $batch->recipients()->withoutGlobalScopes()->exists()) {
                return null;
            }

            $inAppMessage = $this->messages->inAppMessage($school->name, $student->full_name, $entries);

            $batch->update([
                'message' => $inAppMessage,
                'status' => NotificationStatus::Pending->value,
            ]);

            $batch->evaluations()->syncWithoutDetaching($evaluationIds);

            $this->notifyInApp($student, $batch, $inAppMessage);

            $recipientIds = $this->createExternalRecipients(
                $school,
                $student,
                $batch,
                $entries
            );

            $batch->update(['recipient_count' => count($recipientIds)]);

            Evaluation::withoutGlobalScopes()
                ->whereIn('id', $evaluationIds)
                ->update(['notification_status' => EvaluationNotificationStatus::Included->value]);

            return [
                'recipient_count' => count($recipientIds),
                'recipient_ids' => $recipientIds,
            ];
        });
    }

    private function notifyInApp($student, NotificationBatch $batch, string $message): void
    {
        foreach ($student->guardians as $guardian) {
            $user = $guardian->user;

            if (! $user) {
                continue;
            }

            $this->userNotifications->send(
                $user,
                'evaluation.grades',
                'Nouvelles notes disponibles',
                $message,
                [
                    'notification_batch_id' => $batch->id,
                    'student_id' => $student->id,
                    'url' => route('parent.children.show', $student->id),
                ],
                false
            );
        }
    }

    /**
     * @param  array<int, array{subject: string, title: string, score: ?float, max: float, absent: bool}>  $entries
     * @return array<int, int>
     */
    private function createExternalRecipients($school, $student, NotificationBatch $batch, array $entries): array
    {
        $recipientIds = [];
        $seenPhones = [];

        foreach ($student->guardians as $guardian) {
            $phone = $guardian->phone;
            $email = $guardian->email;

            // Père et mère partageant le même numéro : un seul envoi externe.
            if ($phone && in_array($phone, $seenPhones, true)) {
                $phone = null;
            } elseif ($phone) {
                $seenPhones[] = $phone;
            }

            $channel = $this->channelService->select($school, $phone, $email);

            if (! $channel) {
                continue;
            }

            $built = $this->messages->externalMessage($channel, $school->name, $student->full_name, $entries);
            [$cost, $currency] = $this->budget->costFor($channel);

            $idempotencyKey = hash('sha256', implode('|', [
                'batch',
                $batch->id,
                $guardian->id,
                $channel->value,
            ]));

            $notification = Notification::withoutGlobalScopes()->firstOrCreate(
                ['idempotency_key' => $idempotencyKey],
                [
                    'school_id' => $school->id,
                    'notification_batch_id' => $batch->id,
                    'notification_type' => 'validated_evaluation',
                    'evaluation_id' => null,
                    'student_id' => $student->id,
                    'parent_id' => $guardian->id,
                    'channel' => $channel->value,
                    'phone' => $channel === CommunicationChannel::Email ? null : $guardian->phone,
                    'email' => $channel === CommunicationChannel::Email ? $email : null,
                    'message' => $built['message'],
                    'status' => NotificationStatus::Pending->value,
                    'estimated_cost' => (int) ceil($cost),
                    'cost_currency' => $currency,
                ]
            );

            if ($notification->wasRecentlyCreated) {
                $recipientIds[] = $notification->id;
            }
        }

        return $recipientIds;
    }

    /**
     * @param  array<int, int>  $recipientIds
     */
    private function queueRecipients(array $recipientIds): void
    {
        if ($recipientIds === []) {
            return;
        }

        $size = max(1, (int) config('communication.batch_size', 25));
        $interval = max(0, (int) config('communication.batch_interval_minutes', 10));

        foreach (array_chunk($recipientIds, $size) as $index => $chunk) {
            $delay = now()->addMinutes($index * $interval);

            foreach ($chunk as $id) {
                SendCommunicationJob::dispatch($id)->delay($delay);
            }
        }
    }
}
