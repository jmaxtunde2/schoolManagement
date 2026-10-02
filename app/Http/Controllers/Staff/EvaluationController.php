<?php

namespace App\Http\Controllers\Staff;

use App\Actions\Evaluations\CreateEvaluation;
use App\Actions\Evaluations\SaveGrades;
use App\Actions\Evaluations\SubmitEvaluation;
use App\Enums\EvaluationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Evaluations\StoreEvaluationRequest;
use App\Http\Requests\Evaluations\UpdateGradesRequest;
use App\Models\Evaluation;
use App\Models\Teacher;
use App\Services\TwoFactor\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class EvaluationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Evaluations list
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): Response
    {
        $user = $request->user();

        $evaluations = Evaluation::query()
            ->with([
                'classRoom:id,name',
                'subject:id,name',
            ])
            ->when(
                $user->isTeacher(),
                fn ($query) => $query->where(
                    'teacher_id',
                    $user->teacher?->id
                )
            )
            ->latest('evaluation_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render(
            'Teacher/Evaluations/Index',
            [
                'evaluations' => $evaluations,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create evaluation
    |--------------------------------------------------------------------------
    */

    public function create(Request $request): Response
    {
        $user = $request->user();

        $assignments = $user->isTeacher()
            ? $this->teacherAssignments($user)
            : $this->allTeacherAssignments($user);

        return Inertia::render(
            'Teacher/Evaluations/Create',
            [
                'assignments' => $assignments,
                'types' => EvaluationType::options(),
                'isSecretary' => $user->isSecretary(),
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store evaluation
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreEvaluationRequest $request,
        CreateEvaluation $action
    ): RedirectResponse {
        $user = $request->user();

        try {
            /*
             * A teacher uses his own Teacher record.
             * A secretary/admin can select a teacher.
             */
            $teacherId = $user->isTeacher()
                ? $user->teacher?->id
                : (int) $request->validated('teacher_id');

            if (! $teacherId) {
                return back()->with(
                    'error',
                    'Aucun profil enseignant n’est associé à ce compte.'
                );
            }

            $teacher = Teacher::withoutGlobalScopes()
                ->where('school_id', $user->school_id)
                ->findOrFail($teacherId);

            /*
             * A teacher can only create an evaluation
             * for one of his assigned classes/subjects.
             */
            if (
                $user->isTeacher()
                && ! $teacher->isAssignedTo(
                    (int) $request->validated('class_id'),
                    (int) $request->validated('subject_id')
                )
            ) {
                abort(403);
            }

            $evaluation = $action->handle(
                $teacher,
                $request->validated(),
                $user->id
            );
        } catch (RuntimeException $e) {
            return back()->with(
                'error',
                $e->getMessage()
            );
        }

        return redirect()
            ->route(
                'teacher.evaluations.grades',
                $evaluation
            )
            ->with(
                'success',
                'Évaluation créée. Vous pouvez saisir les notes.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Grade entry
    |--------------------------------------------------------------------------
    */

    public function grades(
        Request $request,
        Evaluation $evaluation
    ): Response {
        Gate::authorize('view', $evaluation);

        $evaluation->load([
            'classRoom:id,name',
            'subject:id,name',
            'teacher.user:id,name',
            'enteredBy:id,name',
            'submitter:id,name',
            'validator:id,name',
            'results.student' => fn ($query) => $query
                ->orderBy('last_name')
                ->orderBy('first_name'),
        ]);

        return Inertia::render(
            'Teacher/Evaluations/Grades',
            [
                'evaluation' => [
                    'id' => $evaluation->id,

                    'title' => $evaluation->title,

                    'type' => $evaluation->type->label(),

                    'evaluation_date' =>
                        $evaluation->evaluation_date->format('Y-m-d'),

                    'max_score' =>
                        (float) $evaluation->max_score,

                    'coefficient' =>
                        (float) $evaluation->coefficient,

                    'status' =>
                        $evaluation->status->value,

                    'status_label' =>
                        $evaluation->status->label(),

                    'return_reason' =>
                        $evaluation->return_reason,

                    'class_name' =>
                        $evaluation->classRoom->name,

                    'subject_name' =>
                        $evaluation->subject->name,

                    'teacher_name' =>
                        $evaluation->teacher->user->name,

                    'entered_by' =>
                        $evaluation->enteredBy?->name,

                    'submitted_by' =>
                        $evaluation->submitter?->name,

                    'validated_by' =>
                        $evaluation->validator?->name,

                    'is_validated' =>
                        $evaluation->isValidated(),

                    'can_edit' =>
                        Gate::allows(
                            'update',
                            $evaluation
                        ),

                    'can_submit' =>
                        Gate::allows(
                            'submit',
                            $evaluation
                        ),

                    'can_validate' =>
                        Gate::allows(
                            'validateEvaluation',
                            $evaluation
                        ),

                    'can_return' =>
                        Gate::allows(
                            'returnEvaluation',
                            $evaluation
                        ),

                    /*
                     * We use a relative route because the 2FA controller
                     * only accepts internal relative URLs.
                     */
                    'validation_route' =>
                        $request->user()->isCenseur()
                            ? 'censeur.evaluations.validate'
                            : 'admin.evaluations.validate',

                    'return_route' =>
                        $request->user()->isCenseur()
                            ? 'censeur.evaluations.return'
                            : 'admin.evaluations.return',
                ],

                'results' => $evaluation->results->map(
                    fn ($result) => [
                        'id' => $result->id,

                        'student_id' =>
                            $result->student_id,

                        'student_name' =>
                            $result->student->full_name,

                        'score' =>
                            $result->score !== null
                                ? (float) $result->score
                                : null,

                        'is_absent' =>
                            (bool) $result->is_absent,
                    ]
                ),
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Save grades
    |--------------------------------------------------------------------------
    */

    public function updateGrades(
        UpdateGradesRequest $request,
        Evaluation $evaluation,
        SaveGrades $action
    ): RedirectResponse {
        Gate::authorize('update', $evaluation);

        $action->handle(
            $evaluation,
            $request->validated('results'),
            $request->user()->id
        );

        return back()->with(
            'success',
            'Notes enregistrées en brouillon.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Submit evaluation
    |--------------------------------------------------------------------------
    */

    public function submit(
        Request $request,
        Evaluation $evaluation,
        SubmitEvaluation $action,
        TwoFactorService $twoFactor
    ): RedirectResponse {
        Gate::authorize('submit', $evaluation);

        /*
         * A fresh Authenticator verification is required
         * before submitting an evaluation.
         */
        if (
            ! $twoFactor->sensitiveVerified(
                $request,
                'submit_evaluation'
            )
        ) {
            /*
             * IMPORTANT:
             *
             * The third argument "false" makes route() return
             * a relative URL instead of an absolute URL.
             *
             * Example:
             * /teacher/evaluations/12/grades
             *
             * This is required by TwoFactorController::verify().
             */
            $returnUrl = route(
                'teacher.evaluations.grades',
                $evaluation,
                false
            );

            return redirect()
                ->route(
                    'two-factor.challenge',
                    [
                        'purpose' => 'submit_evaluation',
                        'return' => $returnUrl,
                    ]
                )
                ->with(
                    'error',
                    'Un code Authenticator est requis pour soumettre cette évaluation.'
                );
        }

        try {
            $action->handle(
                $evaluation,
                $request->user()->id
            );
        } catch (RuntimeException $e) {
            return back()->with(
                'error',
                $e->getMessage()
            );
        }

        return back()->with(
            'success',
            'Évaluation soumise. Elle est maintenant en cours de validation.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Private helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Get assignments belonging to the authenticated teacher.
     */
    private function teacherAssignments($user): Collection
    {
        if (! $user->teacher) {
            return collect();
        }

        $teacher = $user->teacher->load([
            'assignments.classRoom:id,name',
            'assignments.subject:id,name',
        ]);

        return $teacher->assignments
            ->map(
                fn ($assignment) => [
                    'class_id' =>
                        $assignment->class_id,

                    'class_name' =>
                        $assignment->classRoom->name,

                    'subject_id' =>
                        $assignment->subject_id,

                    'subject_name' =>
                        $assignment->subject->name,

                    'teacher_id' =>
                        $teacher->id,
                ]
            )
            ->values();
    }

    /**
     * Get all teacher assignments for staff users.
     */
    private function allTeacherAssignments($user): Collection
    {
        return Teacher::where(
            'school_id',
            $user->school_id
        )
            ->with([
                'assignments.classRoom:id,name',
                'assignments.subject:id,name',
                'user:id,name',
            ])
            ->get()
            ->flatMap(
                fn ($teacher) => $teacher->assignments->map(
                    fn ($assignment) => [
                        'class_id' =>
                            $assignment->class_id,

                        'class_name' =>
                            $assignment->classRoom->name,

                        'subject_id' =>
                            $assignment->subject_id,

                        'subject_name' =>
                            $assignment->subject->name,

                        'teacher_id' =>
                            $teacher->id,

                        'teacher_name' =>
                            $teacher->user->name,
                    ]
                )
            )
            ->values();
    }
}