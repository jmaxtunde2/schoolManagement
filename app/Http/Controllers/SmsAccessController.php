<?php

namespace App\Http\Controllers;

use App\Enums\EvaluationStatus;
use App\Models\Evaluation;
use App\Models\EvaluationResult;
use App\Models\NotificationBatch;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\SmsNotificationAccessToken;
use App\Models\Student;
use App\Services\Communication\SmsAccessTokenService;
use App\Support\SchoolBranding;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Point d'entrée public du lien court reçu par SMS.
 *
 * Le token est le seul élément fourni par l'URL. Toutes les données
 * (parent, élève, évaluation, école) sont résolues côté serveur à partir
 * de l'enregistrement en base : impossible de consulter l'élève d'un autre
 * parent en modifiant l'URL.
 */
class SmsAccessController extends Controller
{
    public function __invoke(
        Request $request,
        string $token,
        SmsAccessTokenService $tokens
    ): InertiaResponse|SymfonyResponse {
        $record = $tokens->findByPlainToken($token);

        if (! $record) {
            Log::warning('sms.access.invalid_token', ['ip' => $request->ip()]);

            return $this->unavailable(
                $request,
                404,
                'Lien invalide',
                'Ce lien de consultation est invalide.',
                'Vérifiez que le lien a été copié en entier, ou connectez-vous à votre espace parent.'
            );
        }

        $school = School::query()->find($record->school_id);

        if (! $school || ! $school->is_active) {
            Log::warning('sms.access.inactive_school', [
                'access_token_id' => $record->id,
                'school_id' => $record->school_id,
            ]);

            return $this->unavailable(
                $request,
                404,
                'Lien invalide',
                'Ce lien de consultation n’est plus disponible.',
                'Connectez-vous à votre espace parent pour consulter vos résultats.'
            );
        }

        if ($record->isRevoked()) {
            Log::warning('sms.access.revoked_token', [
                'access_token_id' => $record->id,
                'school_id' => $record->school_id,
            ]);

            return $this->unavailable(
                $request,
                410,
                'Lien révoqué',
                'Ce lien a été révoqué.',
                'Veuillez vous connecter à votre espace parent pour consulter vos résultats.',
                $school
            );
        }

        if ($record->isExpired()) {
            Log::warning('sms.access.expired_token', [
                'access_token_id' => $record->id,
                'school_id' => $record->school_id,
            ]);

            return $this->unavailable(
                $request,
                410,
                'Lien expiré',
                'Ce lien a expiré.',
                'Veuillez vous connecter à votre espace parent pour consulter vos résultats.',
                $school
            );
        }

        if ($record->hasReachedUsageLimit()) {
            Log::warning('sms.access.usage_limit_reached', [
                'access_token_id' => $record->id,
                'school_id' => $record->school_id,
            ]);

            return $this->unavailable(
                $request,
                429,
                'Lien déjà utilisé',
                'Ce lien a atteint sa limite d’utilisation.',
                'Veuillez vous connecter à votre espace parent pour consulter vos résultats.',
                $school
            );
        }

        $context = $this->resolveContext($record, $school);

        if (! $context) {
            Log::warning('sms.access.context_denied', [
                'access_token_id' => $record->id,
                'school_id' => $record->school_id,
            ]);

            return $this->unavailable(
                $request,
                404,
                'Accès refusé',
                'Les informations associées à ce lien sont introuvables.',
                'Connectez-vous à votre espace parent pour consulter vos résultats.',
                $school
            );
        }

        if (! $tokens->consume($record, $request)) {
            return $this->unavailable(
                $request,
                410,
                'Lien expiré',
                'Ce lien n’est plus utilisable.',
                'Veuillez vous connecter à votre espace parent pour consulter vos résultats.',
                $school
            );
        }

        return Inertia::render('SmsAccess/Show', [
            'school' => SchoolBranding::for($school),
            'parent' => [
                'name' => $context['parent']->name,
            ],
            'student' => [
                'name' => $context['student']->full_name,
                'matricule' => $context['student']->matricule,
                'class_name' => $context['student']->classRoom?->name,
            ],
            'evaluations' => $context['evaluations'],
            'expires_at' => $record->expires_at?->toIso8601String(),
            'login_url' => route('login'),
            'parent_url' => $request->user()?->isParent()
                ? route('parent.dashboard')
                : route('login'),
            'is_authenticated' => $request->user() !== null,
        ]);
    }

    /**
     * @return array{parent: ParentGuardian, student: Student, evaluations: array<int, array<string, mixed>>}|null
     */
    private function resolveContext(SmsNotificationAccessToken $record, School $school): ?array
    {
        $parent = ParentGuardian::withoutGlobalScopes()->find($record->parent_id);
        $student = Student::withoutGlobalScopes()
            ->with('classRoom')
            ->find($record->student_id);

        if (! $parent || ! $student) {
            return null;
        }

        if ((int) $student->school_id !== (int) $school->id) {
            return null;
        }

        $belongsToParent = DB::table('parent_student')
            ->where('parent_id', $parent->id)
            ->where('student_id', $student->id)
            ->exists();

        if (! $belongsToParent) {
            return null;
        }

        $evaluations = $this->evaluationsFor($record, $student);

        if ($evaluations->isEmpty()) {
            return null;
        }

        $results = EvaluationResult::withoutGlobalScopes()
            ->whereIn('evaluation_id', $evaluations->pluck('id'))
            ->where('student_id', $student->id)
            ->get()
            ->keyBy('evaluation_id');

        $payload = $evaluations
            ->filter(fn (Evaluation $evaluation) => $results->has($evaluation->id))
            ->map(function (Evaluation $evaluation) use ($results) {
                $result = $results->get($evaluation->id);

                return [
                    'title' => $evaluation->title,
                    'subject' => $evaluation->subject?->name,
                    'type' => $evaluation->type?->label(),
                    'date' => $evaluation->evaluation_date?->format('d/m/Y'),
                    'period' => $evaluation->academicPeriod?->name,
                    'score' => $result->is_absent || $result->score === null
                        ? null
                        : (float) $result->score,
                    'max_score' => (float) $evaluation->max_score,
                    'coefficient' => (float) $evaluation->coefficient,
                    'is_absent' => (bool) $result->is_absent,
                    // L'appréciation n'existe pas encore au niveau d'une
                    // évaluation ; le champ est prêt pour une évolution.
                    'appreciation' => null,
                ];
            })
            ->values()
            ->all();

        if ($payload === []) {
            return null;
        }

        return [
            'parent' => $parent,
            'student' => $student,
            'evaluations' => $payload,
        ];
    }

    /**
     * @return Collection<int, Evaluation>
     */
    private function evaluationsFor(
        SmsNotificationAccessToken $record,
        Student $student
    ): Collection {
        $query = Evaluation::withoutGlobalScopes()
            ->where('school_id', $record->school_id)
            ->where('status', EvaluationStatus::Validated->value)
            ->with(['subject', 'classRoom', 'academicPeriod']);

        if ($record->evaluation_id) {
            return $query
                ->whereKey($record->evaluation_id)
                ->get();
        }

        if (! $record->notification_batch_id) {
            return collect();
        }

        $batch = NotificationBatch::withoutGlobalScopes()->find($record->notification_batch_id);

        if (
            ! $batch
            || (int) $batch->school_id !== (int) $record->school_id
            || (int) $batch->student_id !== (int) $student->id
        ) {
            return collect();
        }

        return $batch->evaluations()
            ->withoutGlobalScopes()
            ->where('status', EvaluationStatus::Validated->value)
            ->with(['subject', 'classRoom', 'academicPeriod'])
            ->get();
    }

    private function unavailable(
        Request $request,
        int $status,
        string $title,
        string $message,
        string $hint,
        ?School $school = null
    ): SymfonyResponse {
        return Inertia::render('SmsAccess/Unavailable', [
            'status' => $status,
            'title' => $title,
            'message' => $message,
            'hint' => $hint,
            'school' => SchoolBranding::for($school),
            'login_url' => route('login'),
        ])->toResponse($request)->setStatusCode($status);
    }
}
