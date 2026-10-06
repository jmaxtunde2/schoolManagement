<?php

namespace App\Services\UserNotifications;

use App\Models\Evaluation;
use App\Models\User;
use Illuminate\Support\Collection;

class EvaluationNotificationService
{
    public function __construct(
        private readonly UserNotificationService $userNotifications
    ) {}

    public function notify(Evaluation $evaluation, string $event): int
    {
        $evaluation = $evaluation->fresh(['teacher.user', 'school']);

        $relevantUsers = $this->recipientsFor($evaluation, $event);
        $count = 0;

        foreach ($relevantUsers as $user) {
            if (! $user || ! $user->is_active) {
                continue;
            }

            $title = $this->titleFor($event, $evaluation);
            $message = $this->messageFor($event, $evaluation, $user);
            $data = [
                'evaluation_id' => $evaluation->id,
                'url' => $this->urlFor($user, $evaluation),
            ];

            $this->userNotifications->send(
                $user,
                $event,
                $title,
                $message,
                $data,
                true
            );

            $count++;
        }

        return $count;
    }

    protected function recipientsFor(Evaluation $evaluation, string $event): Collection
    {
        $schoolUsers = User::query()
            ->where('school_id', $evaluation->school_id)
            ->where('is_active', true)
            ->get();

        return match ($event) {
            'evaluation.created', 'evaluation.submitted', 'evaluation.resubmitted' => $schoolUsers->filter(
                fn (User $user) => $user->isAdmin() || $user->isCenseur()
            ),
            'evaluation.returned' => $evaluation->teacher?->user
                ? collect([$evaluation->teacher->user])
                : collect(),
            'evaluation.validated' => $evaluation->teacher?->user
                ? collect([$evaluation->teacher->user])
                : collect(),
            default => collect(),
        };
    }

    protected function titleFor(string $event, Evaluation $evaluation): string
    {
        return match ($event) {
            'evaluation.created' => 'Nouvelle évaluation créée',
            'evaluation.submitted' => 'Évaluation soumise',
            'evaluation.returned' => 'Évaluation retournée pour correction',
            'evaluation.resubmitted' => 'Évaluation corrigée et resoumise',
            'evaluation.validated' => 'Évaluation validée',
            default => 'Notification d\'évaluation',
        };
    }

    protected function messageFor(string $event, Evaluation $evaluation, User $user): string
    {
        $subject = $evaluation->subject?->name ?? 'Matière';
        $class = $evaluation->classRoom?->name ?? 'Classe';
        $teacher = $evaluation->teacher?->user?->name ?? 'Équipe';

        return match ($event) {
            'evaluation.created' => sprintf(
                'Une nouvelle évaluation "%s" a été créée pour la classe %s en %s. Elle attend validation.',
                $evaluation->title,
                $class,
                $subject
            ),
            'evaluation.submitted' => sprintf(
                'L\'évaluation "%s" a été soumise par %s pour validation.',
                $evaluation->title,
                $teacher
            ),
            'evaluation.returned' => sprintf(
                'Votre évaluation "%s" a été renvoyée pour correction. Motif : %s',
                $evaluation->title,
                trim((string) ($evaluation->return_reason ?? 'Aucun motif fourni'))
            ),
            'evaluation.resubmitted' => sprintf(
                'L\'évaluation "%s" a été corrigée et resoumise pour validation.',
                $evaluation->title
            ),
            'evaluation.validated' => sprintf(
                'Votre évaluation "%s" a été validée par l\'administration.',
                $evaluation->title
            ),
            default => sprintf('Une mise à jour concerne l\'évaluation "%s".', $evaluation->title),
        };
    }

    protected function urlFor(User $user, Evaluation $evaluation): string
    {
        if ($user->isAdmin() || $user->isCenseur()) {
            return route('admin.evaluations.grades', ['evaluation' => $evaluation->id]);
        }

        if ($user->isTeacher()) {
            return route('teacher.evaluations.grades', ['evaluation' => $evaluation->id]);
        }

        return '/';
    }
}
