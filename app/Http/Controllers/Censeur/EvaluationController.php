<?php

namespace App\Http\Controllers\Censeur;

use App\Actions\Evaluations\CreateEvaluation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Evaluations\StoreEvaluationRequest;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\Evaluation;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EvaluationController extends Controller
{
    public function index(Request $request)
    {
        $evaluations = Evaluation::with(['classRoom:id,name', 'subject:id,name', 'teacher.user:id,name'])->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))->latest('evaluation_date')->latest('id')->paginate(20)->withQueryString();

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
            $teacherId = (int) $request->validated('teacher_id');

            $teacher = Teacher::withoutGlobalScopes()
                ->where('school_id', $user->school_id)
                ->findOrFail($teacherId);

            $validated = $request->validated();
            $validateDirectly = $validated['validate_directly'] ?? false;
            unset($validated['validate_directly']);

            $evaluation = $action->handle(
                $teacher,
                $validated,
                $user->id,
                $validateDirectly
            );

            if ($validateDirectly) {
                return redirect()
                    ->route('censeur.evaluations.grades', $evaluation)
                    ->with('success', 'Évaluation créée. Vous pouvez saisir les notes puis la valider directement.');
            }

            return redirect()
                ->route('censeur.evaluations.grades', $evaluation)
                ->with('success', 'Évaluation créée en brouillon. Vous pouvez saisir les notes.');
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
