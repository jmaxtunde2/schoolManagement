<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Academic\GenerateReportCards;
use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\ReportCard;
use App\Services\Academic\AcademicCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AcademicResultController extends Controller
{
    public function index(Request $request, AcademicCalculationService $calculator): Response
    {
        $schoolId = $request->user()->school_id;
        $filters = $request->validate([
            'class_id' => ['nullable', 'integer', Rule::exists('classes', 'id')->where('school_id', $schoolId)],
            'period_id' => ['nullable', 'integer', Rule::exists('academic_periods', 'id')->where('school_id', $schoolId)],
        ]);

        $classesQuery = ClassRoom::query()->orderBy('name');
        if ($request->user()->isTeacher()) {
            $assignedClassIds = $request->user()->teacher?->classes()->pluck('classes.id') ?? collect();
            $classesQuery->whereIn('id', $assignedClassIds);
        }
        $classes = $classesQuery->get(['id', 'name']);
        $periods = AcademicPeriod::query()
            ->with('academicYear:id,name')
            ->orderByDesc('academic_year_id')
            ->orderBy('position')
            ->get(['id', 'academic_year_id', 'name', 'position', 'is_closed']);

        $results = null;
        $reportCards = collect();
        if (! empty($filters['class_id']) && ! empty($filters['period_id'])) {
            $classRoom = ClassRoom::findOrFail($filters['class_id']);
            $period = AcademicPeriod::findOrFail($filters['period_id']);
            Gate::authorize('viewAcademicResults', [ClassRoom::class, $classRoom, $period]);

            $results = $calculator->calculateClassResults($classRoom, $period, (int) $schoolId);
            $reportCards = ReportCard::query()
                ->where('class_room_id', $classRoom->id)
                ->where('academic_period_id', $period->id)
                ->get(['id', 'student_id', 'status', 'version', 'general_average', 'rank'])
                ->keyBy('student_id');
        }

        return Inertia::render('Admin/Academic/Results', [
            'classes' => $classes,
            'periods' => $periods->map(fn (AcademicPeriod $period) => [
                'id' => $period->id,
                'name' => $period->name,
                'position' => $period->position,
                'is_closed' => $period->is_closed,
                'academic_year' => $period->academicYear?->name,
            ]),
            'filters' => $filters,
            'results' => $results ? [
                ...$results,
                'students' => $results['students']->map(function (array $student) use ($reportCards) {
                    $student['report_card'] = $reportCards->get($student['student_id'])?->only([
                        'id', 'status', 'version', 'general_average', 'rank',
                    ]);

                    return $student;
                })->values(),
            ] : null,
            'routePrefix' => $request->routeIs('censeur.*')
                ? 'censeur'
                : ($request->routeIs('teacher.*')
                    ? 'teacher'
                    : ($request->routeIs('secretary.*') ? 'secretary' : 'admin')),
        ]);
    }

    public function generate(Request $request, GenerateReportCards $action): RedirectResponse
    {
        $data = $request->validate([
            'class_id' => ['required', 'integer', Rule::exists('classes', 'id')->where('school_id', $request->user()->school_id)],
            'period_id' => ['required', 'integer', Rule::exists('academic_periods', 'id')->where('school_id', $request->user()->school_id)],
            'student_id' => ['nullable', 'integer', Rule::exists('students', 'id')->where('school_id', $request->user()->school_id)],
        ]);

        $classRoom = ClassRoom::findOrFail($data['class_id']);
        $period = AcademicPeriod::findOrFail($data['period_id']);
        Gate::authorize('viewAcademicResults', [ClassRoom::class, $classRoom, $period]);
        Gate::authorize('generate', ReportCard::class);

        try {
            $generated = $action->handle(
                $request->user()->school,
                $classRoom,
                $period,
                $request->user()->id,
                $data['student_id'] ?? null,
            );
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with(
            'success',
            "{$generated['generated']} bulletin(s) généré(s); {$generated['skipped']} élève(s) sans résultat calculable ignoré(s)."
        );
    }
}