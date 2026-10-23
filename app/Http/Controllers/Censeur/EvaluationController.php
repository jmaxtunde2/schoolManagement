<?php

namespace App\Http\Controllers\Censeur;

use App\Actions\Evaluations\CreateEvaluation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Evaluations\StoreEvaluationRequest;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EvaluationController extends Controller
{
    public function index(Request $request)
    {
        $evaluations = Evaluation::with([
            'classRoom:id,name',
            'subject:id,name',
            'teacher.user:id,name',
            'enteredBy:id,name,role',
            'submitter:id,name,role',
        ])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('created_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $evaluations->through(function (Evaluation $evaluation) {
            $data = $evaluation->toArray();
            unset($data['entered_by']);

            return [
                ...$data,
                'entered_by_role' => $evaluation->enteredBy?->role?->value
                    ?? $evaluation->submitter?->role?->value,
                'can_validate' => Gate::allows('validateEvaluation', $evaluation),
                'can_return' => Gate::allows('returnEvaluation', $evaluation),
            ];
        });

        return Inertia::render('Admin/Evaluations/Index', ['evaluations' => $evaluations, 'filters' => $request->only('status'), 'routePrefix' => 'censeur']);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();

        $assignments = $this->allTeacherAssignments($user);

        $academicYear = AcademicYear::query()
            ->where('school_id', $user->school_id)
            ->where('is_current', true)
            ->latest('id')
            ->first()
            ?? AcademicYear::query()
                ->where('school_id', $user->school_id)
                ->latest('id')
                ->first();

        $academicPeriod = $academicYear
            ? AcademicPeriod::query()
                ->where('school_id', $user->school_id)
                ->where('academic_year_id', $academicYear->id)
                ->where('is_closed', false)
                ->whereDate('starts_at', '<=', now())
                ->whereDate('ends_at', '>=', now())
                ->orderBy('position')
                ->first()
            : null;

        return Inertia::render(
            'Censeur/Evaluations/Create',
            [
                'assignments' => $assignments,
                'classes' => ClassRoom::query()
                    ->with('subjects:id,name')
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (ClassRoom $classRoom) => [
                        'id' => $classRoom->id,
                        'name' => $classRoom->name,
                        'subjects' => $classRoom->subjects->map(fn (Subject $subject) => [
                            'id' => $subject->id,
                            'name' => $subject->name,
                        ])->values(),
                    ])
                    ->values(),
                'types' => \App\Enums\EvaluationType::options(),
                'isCenseur' => true,
                'academicYear' => $academicYear ? [
                    'id' => $academicYear->id,
                    'name' => $academicYear->name,
                ] : null,
                'academicPeriod' => $academicPeriod ? [
                    'id' => $academicPeriod->id,
                    'name' => $academicPeriod->name,
                ] : null,
            ]
        );
    }

    public function store(
        StoreEvaluationRequest $request,
        CreateEvaluation $action
    ): RedirectResponse {
        $user = $request->user();

        try {
            $validated = $request->validated();
            $teacherId = filled($validated['teacher_id'] ?? null)
                ? (int) $validated['teacher_id']
                : null;
            if (! $teacherId) {
                $teacherId = Teacher::withoutGlobalScopes()
                    ->where('school_id', $user->school_id)
                    ->whereHas('user', fn ($query) => $query->where('is_active', true))
                    ->whereHas('assignments', fn ($query) => $query
                        ->where('class_id', $validated['class_id'])
                        ->where('subject_id', $validated['subject_id']))
                    ->value('id');
            }
            unset($validated['teacher_id']);

            $teacher = $teacherId
                ? Teacher::withoutGlobalScopes()
                    ->where('school_id', $user->school_id)
                    ->findOrFail($teacherId)
                : null;

            $evaluation = $action->handle(
                $teacher,
                $validated,
                $user->id,
                (int) $user->school_id
            );

            return redirect()
                ->route('censeur.evaluations.grades', $evaluation)
                ->with('success', 'Évaluation créée en brouillon. Saisissez les notes puis validez-la, ou laissez-la en brouillon.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function allTeacherAssignments($user): \Illuminate\Support\Collection
    {
        return Teacher::query()
            ->where('school_id', $user->school_id)
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->with([
                'assignments.classRoom:id,name',
                'assignments.subject:id,name',
                'user:id,name',
            ])
            ->get()
            ->flatMap(
                fn ($teacher) => $teacher->assignments->map(
                    fn ($assignment) => [
                        'class_id' => $assignment->class_id,
                        'class_name' => $assignment->classRoom->name,
                        'subject_id' => $assignment->subject_id,
                        'subject_name' => $assignment->subject->name,
                        'teacher_id' => $teacher->id,
                        'teacher_name' => $teacher->user->name,
                    ]
                )
            )
            ->values();
    }
}
