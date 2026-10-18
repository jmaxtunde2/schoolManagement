<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Academic\BuildResultsPerformance;
use App\Actions\Academic\EducMasterResultsExport;
use App\Actions\Academic\GenerateReportCards;
use App\Actions\Academic\PrepareEducMasterExport;
use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\ReportCard;
use App\Models\School;
use App\Services\Academic\AcademicCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AcademicResultController extends Controller
{
    public function index(
        Request $request,
        AcademicCalculationService $calculator,
        BuildResultsPerformance $performance,
    ): Response {
        $schoolId = $request->user()->school_id;
        $filters = $request->validate([
            'class_id' => ['nullable', 'integer', Rule::exists('classes', 'id')->where('school_id', $schoolId)],
            'period_id' => ['nullable', 'integer', Rule::exists('academic_periods', 'id')->where('school_id', $schoolId)],
        ]);

        $performanceFilters = $request->validate([
            'tab' => ['nullable', Rule::in(['results', 'performance'])],
            'perf_mode' => ['nullable', Rule::in(BuildResultsPerformance::MODES)],
            'perf_class_id' => ['nullable', 'integer', Rule::exists('classes', 'id')->where('school_id', $schoolId)],
            'perf_academic_year_id' => ['nullable', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $schoolId)],
        ]);

        $classesQuery = ClassRoom::query()->orderBy('name');
        if ($request->user()->isTeacher()) {
            $assignedClassIds = $request->user()->teacher?->classes()->pluck('classes.id') ?? collect();
            $classesQuery->whereIn('id', $assignedClassIds);
        } elseif ($request->user()->isDirector()) {
            $classesQuery->primary();
        }
        $classes = $classesQuery->get(['id', 'name']);
        $periods = AcademicPeriod::query()
            ->with('academicYear:id,name')
            ->orderByDesc('academic_year_id')
            ->orderBy('position')
            ->get(['id', 'academic_year_id', 'name', 'position', 'is_closed']);

        $perfClassId = isset($performanceFilters['perf_class_id'])
            ? (int) $performanceFilters['perf_class_id']
            : null;
        $perfMode = $performanceFilters['perf_mode'] ?? 'periods';
        $perfYearId = isset($performanceFilters['perf_academic_year_id'])
            ? (int) $performanceFilters['perf_academic_year_id']
            : null;

        $allowedClassIds = $classes->pluck('id')->all();

        if ($perfClassId !== null && ! in_array($perfClassId, $allowedClassIds, true)) {
            abort(403);
        }

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

        $performanceData = ($performanceFilters['tab'] ?? null) === 'performance'
            || $request->filled('perf_mode')
            || $request->filled('perf_class_id')
            || $request->filled('perf_academic_year_id')
            ? $performance->handle(
                (int) $schoolId,
                $allowedClassIds,
                $perfMode,
                $perfClassId,
                $perfYearId,
            )
            : null;

        $academicYears = AcademicYear::query()
            ->orderByDesc('starts_on')
            ->get(['id', 'name']);

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
            'performance' => $performanceData,
            'performanceFilters' => [
                'mode' => $perfMode,
                'class_id' => $perfClassId,
                'academic_year_id' => $perfYearId,
            ],
            'academicYears' => $academicYears,
            'initialTab' => ($performanceFilters['tab'] ?? 'results') === 'performance'
                ? 'performance'
                : 'results',
            'results' => $results ? [
                ...$results,
                'students' => $results['students']->map(function (array $student) use ($reportCards) {
                    $student['report_card'] = $reportCards->get($student['student_id'])?->only([
                        'id', 'status', 'version', 'general_average', 'rank',
                    ]);

                    return $student;
                })->values(),
            ] : null,
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    private function routePrefix(Request $request): string
    {
        return match (true) {
            $request->routeIs('director.*') => 'director',
            $request->routeIs('censeur.*') => 'censeur',
            $request->routeIs('teacher.*') => 'teacher',
            $request->routeIs('secretary.*') => 'secretary',
            default => 'admin',
        };
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

    public function prepareEducMaster(
        Request $request,
        AcademicCalculationService $calculator,
    ): Response {
        $data = $request->validate([
            'class_id' => ['required', 'integer', Rule::exists('classes', 'id')->where('school_id', $request->user()->school_id)],
            'period_id' => ['required', 'integer', Rule::exists('academic_periods', 'id')->where('school_id', $request->user()->school_id)],
        ]);

        $classRoom = ClassRoom::findOrFail($data['class_id']);
        $period = AcademicPeriod::findOrFail($data['period_id']);
        $school = School::findOrFail($request->user()->school_id);

        Gate::authorize('viewAcademicResults', [ClassRoom::class, $classRoom, $period]);

        $report = (new PrepareEducMasterExport($classRoom, $period, $calculator, $school))->handle();

        return Inertia::render('Admin/Academic/EducMasterPreparation', [
            'report' => $report,
            'classRoom' => ['id' => $classRoom->id, 'name' => $classRoom->name],
            'period' => ['id' => $period->id, 'name' => $period->name],
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    public function exportEducMaster(Request $request, AcademicCalculationService $calculator): BinaryFileResponse
    {
        $data = $request->validate([
            'class_id' => ['required', 'integer', Rule::exists('classes', 'id')->where('school_id', $request->user()->school_id)],
            'period_id' => ['required', 'integer', Rule::exists('academic_periods', 'id')->where('school_id', $request->user()->school_id)],
        ]);

        $classRoom = ClassRoom::findOrFail($data['class_id']);
        $period = AcademicPeriod::findOrFail($data['period_id']);
        $school = School::findOrFail($request->user()->school_id);

        Gate::authorize('viewAcademicResults', [ClassRoom::class, $classRoom, $period]);

        $export = new EducMasterResultsExport($classRoom, $period, $calculator, $school);

        $fileName = sprintf(
            'educmaster_%s_%s_%s.xlsx',
            str($classRoom->name)->slug(),
            str($period->name)->slug(),
            now()->format('Ymd_His')
        );

        return Excel::download($export, $fileName);
    }
}
