<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Academic\EducMaster\BuildEducMasterExportSummary;
use App\Actions\Academic\EducMaster\EducMasterClassesExport;
use App\Actions\Academic\EducMaster\EducMasterEvaluationNotesExport;
use App\Actions\Academic\EducMaster\EducMasterStudentsExport;
use App\Actions\Academic\EducMaster\EducMasterTimetableExport;
use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EducMasterExportController extends Controller
{
    public function index(Request $request, BuildEducMasterExportSummary $summary): Response
    {
        $schoolId = (int) $request->user()->school_id;

        $academicYears = AcademicYear::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->orderByDesc('starts_on')
            ->get(['id', 'name', 'is_current']);

        $periods = AcademicPeriod::query()
            ->with('academicYear:id,name')
            ->orderByDesc('academic_year_id')
            ->orderBy('position')
            ->get(['id', 'academic_year_id', 'name', 'position']);

        return Inertia::render('Admin/EducMaster/Index', [
            'academicYears' => $academicYears,
            'classes' => $this->scopedClasses($request)->get(['id', 'name']),
            'periods' => $periods->map(fn (AcademicPeriod $period) => [
                'id' => $period->id,
                'name' => $period->name,
                'academic_year_id' => $period->academic_year_id,
                'academic_year' => $period->academicYear?->name,
            ]),
            'summary' => $summary->handle($schoolId, $this->allowedClassIds($request)),
            'routePrefix' => $request->user()->isDirector()
                ? 'director'
                : ($request->user()->isCenseur() ? 'censeur' : 'admin'),
        ]);
    }

    public function students(Request $request): BinaryFileResponse
    {
        $school = $this->school($request);
        $data = $request->validate([
            'class_id' => ['nullable', 'integer', Rule::exists('classes', 'id')->where('school_id', $school->id)],
        ]);

        $classId = isset($data['class_id']) ? (int) $data['class_id'] : null;
        $this->ensureClassInScope($request, $classId);

        return Excel::download(
            new EducMasterStudentsExport($school, $classId, $this->allowedClassIds($request)),
            $this->fileName('eleves'),
        );
    }

    public function notes(Request $request): BinaryFileResponse
    {
        $school = $this->school($request);
        $data = $request->validate([
            'academic_year_id' => ['nullable', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $school->id)],
            'class_id' => ['nullable', 'integer', Rule::exists('classes', 'id')->where('school_id', $school->id)],
            'period_id' => ['nullable', 'integer', Rule::exists('academic_periods', 'id')->where('school_id', $school->id)],
        ]);

        $classId = isset($data['class_id']) ? (int) $data['class_id'] : null;
        $this->ensureClassInScope($request, $classId);

        return Excel::download(
            new EducMasterEvaluationNotesExport(
                $school,
                isset($data['academic_year_id']) ? (int) $data['academic_year_id'] : null,
                $classId,
                isset($data['period_id']) ? (int) $data['period_id'] : null,
                $this->allowedClassIds($request),
            ),
            $this->fileName('notes'),
        );
    }

    public function classes(Request $request): BinaryFileResponse
    {
        $school = $this->school($request);

        return Excel::download(
            new EducMasterClassesExport($school, $this->allowedClassIds($request)),
            $this->fileName('classes'),
        );
    }

    public function timetable(Request $request): BinaryFileResponse
    {
        $school = $this->school($request);
        $data = $request->validate([
            'academic_year_id' => ['nullable', 'integer', Rule::exists('academic_years', 'id')->where('school_id', $school->id)],
            'class_id' => ['nullable', 'integer', Rule::exists('classes', 'id')->where('school_id', $school->id)],
        ]);

        $classId = isset($data['class_id']) ? (int) $data['class_id'] : null;
        $this->ensureClassInScope($request, $classId);

        return Excel::download(
            new EducMasterTimetableExport(
                $school,
                isset($data['academic_year_id']) ? (int) $data['academic_year_id'] : null,
                $classId,
                $this->allowedClassIds($request),
            ),
            $this->fileName('emploi_du_temps'),
        );
    }

    private function school(Request $request): School
    {
        return School::findOrFail($request->user()->school_id);
    }

    private function scopedClasses(Request $request): Builder
    {
        $query = ClassRoom::query()->orderBy('name');

        if ($request->user()->isDirector()) {
            $query->primary();
        }

        return $query;
    }

    /**
     * @return array<int, int>|null
     */
    private function allowedClassIds(Request $request): ?array
    {
        if ($request->user()->isDirector()) {
            return ClassRoom::query()->primary()->pluck('id')->all();
        }

        return null;
    }

    private function ensureClassInScope(Request $request, ?int $classId): void
    {
        if ($classId === null) {
            return;
        }

        $allowed = $this->allowedClassIds($request);

        if ($allowed !== null && ! in_array($classId, $allowed, true)) {
            abort(403, "Cette classe n'est pas dans votre périmètre.");
        }
    }

    private function fileName(string $type): string
    {
        return sprintf('educmaster_%s_%s.xlsx', $type, now()->format('Ymd_His'));
    }
}
