<?php

namespace App\Http\Controllers\Staff;

use App\Actions\Attendance\RecordDailyAttendance;
use App\Enums\AttendanceJustificationStatus;
use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StoreAttendanceRequest;
use App\Models\AcademicPeriod;
use App\Models\AttendanceJustification;
use App\Models\AttendanceRecord;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', AttendanceRecord::class);

        $user = $request->user();
        $schoolId = $user->school_id;
        $classIds = $this->classIdsFor($user);
        $query = AttendanceRecord::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->with([
                'student:id,school_id,class_id,first_name,last_name,matricule',
                'classRoom:id,name',
                'recorder:id,name',
                'period:id,name,is_closed',
                'justifications.parent:id,name',
                'justifications.reviewer:id,name',
            ]);

        if ($classIds !== null) {
            $query->whereIn('class_room_id', $classIds);
        }

        $filters = $request->validate([
            'class_id' => ['nullable', 'integer'],
            'student' => ['nullable', 'string', 'max:120'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', Rule::enum(AttendanceStatus::class)],
            'justification' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'unjustified'])],
        ]);

        if (! empty($filters['class_id'])) {
            $query->where('class_room_id', $filters['class_id']);
        }
        if (! empty($filters['student'])) {
            $term = '%'.$filters['student'].'%';
            $query->whereHas('student', fn ($students) => $students
                ->where('first_name', 'like', $term)
                ->orWhere('last_name', 'like', $term)
                ->orWhere('matricule', 'like', $term));
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('attendance_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('attendance_date', '<=', $filters['date_to']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (($filters['justification'] ?? null) === 'unjustified') {
            $query->where('status', AttendanceStatus::Absent->value)
                ->whereNull('justified_at')
                ->whereDoesntHave('justifications', fn ($justifications) => $justifications
                    ->where('status', AttendanceJustificationStatus::Approved->value));
        } elseif (! empty($filters['justification'])) {
            $query->whereHas('justifications', fn ($justifications) => $justifications
                ->where('status', $filters['justification']));
        }

        $stats = AttendanceRecord::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->when($classIds !== null, fn ($records) => $records->whereIn('class_room_id', $classIds));
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $routePrefix = $user->isAdmin()
            ? 'admin'
            : ($user->isCenseur() ? 'censeur' : 'teacher');

        $records = $query
            ->latest('attendance_date')
            ->latest('id')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (AttendanceRecord $record) => [
                'id' => $record->id,
                'attendance_date' => $record->attendance_date->format('Y-m-d'),
                'status' => $record->status->value,
                'delay_minutes' => $record->delay_minutes,
                'reason' => $record->reason,
                'note' => $record->note,
                'justified_at' => $record->justified_at?->toDateTimeString(),
                'student' => [
                    'id' => $record->student->id,
                    'name' => $record->student->full_name,
                    'matricule' => $record->student->matricule,
                ],
                'class_name' => $record->classRoom?->name,
                'period_name' => $record->period?->name,
                'recorder_name' => $record->recorder?->name,
                'justifications' => $record->justifications->map(fn (AttendanceJustification $justification) => [
                    'id' => $justification->id,
                    'parent_name' => $justification->parent?->name,
                    'reason' => $justification->reason,
                    'status' => $justification->status->value,
                    'reviewer_name' => $justification->reviewer?->name,
                ])->values(),
                'can_update' => Gate::allows('update', $record),
                'can_justify' => Gate::allows('justify', $record),
            ]);

        return Inertia::render('Staff/Attendance/Index', [
            'records' => $records,
            'classes' => $this->classesFor($user)->map(fn (ClassRoom $classRoom) => [
                'id' => $classRoom->id,
                'name' => $classRoom->name,
            ]),
            'filters' => $filters,
            'routePrefix' => $routePrefix,
            'stats' => [
                'absent_today' => (clone $stats)->whereDate('attendance_date', $today)->where('status', AttendanceStatus::Absent->value)->count(),
                'late_today' => (clone $stats)->whereDate('attendance_date', $today)->where('status', AttendanceStatus::Late->value)->count(),
                'absent_month' => (clone $stats)->whereDate('attendance_date', '>=', $monthStart)->where('status', AttendanceStatus::Absent->value)->count(),
                'late_month' => (clone $stats)->whereDate('attendance_date', '>=', $monthStart)->where('status', AttendanceStatus::Late->value)->count(),
                'unjustified' => (clone $stats)->where('status', AttendanceStatus::Absent->value)->whereNull('justified_at')->whereDoesntHave('justifications', fn ($justifications) => $justifications->where('status', AttendanceJustificationStatus::Approved->value))->count(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('viewAny', AttendanceRecord::class);
        $user = $request->user();
        $classes = $this->classesFor($user);
        $classRoom = null;
        $students = collect();
        $date = $request->validate([
            'attendance_date' => ['nullable', 'date', 'before_or_equal:today'],
        ])['attendance_date'] ?? now()->toDateString();

        if ($request->filled('class_id')) {
            $classRoom = $classes->firstWhere('id', $request->integer('class_id'));
            abort_unless($classRoom, 404);
            Gate::authorize('create', [AttendanceRecord::class, $classRoom]);

            $existing = AttendanceRecord::withoutGlobalScopes()
                ->where('school_id', $user->school_id)
                ->where('class_room_id', $classRoom->id)
                ->whereDate('attendance_date', $date)
                ->get()
                ->keyBy('student_id');

            $students = Student::query()
                ->where('class_id', $classRoom->id)
                ->where('is_active', true)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'matricule'])
                ->map(fn (Student $student) => [
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'matricule' => $student->matricule,
                    'attendance' => $existing->get($student->id) ? [
                        'status' => $existing->get($student->id)->status->value,
                        'delay_minutes' => $existing->get($student->id)->delay_minutes,
                        'reason' => $existing->get($student->id)->reason,
                        'note' => $existing->get($student->id)->note,
                    ] : null,
                ]);
        }

        $periods = AcademicPeriod::query()
            ->where('is_closed', false)
            ->whereDate('starts_at', '<=', $date)
            ->whereDate('ends_at', '>=', $date)
            ->orderBy('position')
            ->get(['id', 'name']);

        return Inertia::render('Staff/Attendance/Create', [
            'classes' => $classes->map(fn (ClassRoom $item) => ['id' => $item->id, 'name' => $item->name]),
            'selectedClass' => $classRoom ? ['id' => $classRoom->id, 'name' => $classRoom->name] : null,
            'students' => $students,
            'attendanceDate' => $date,
            'periods' => $periods,
            'routePrefix' => $user->isAdmin() ? 'admin' : ($user->isCenseur() ? 'censeur' : 'teacher'),
        ]);
    }

    public function store(StoreAttendanceRequest $request, RecordDailyAttendance $action): RedirectResponse
    {
        $classRoom = ClassRoom::withoutGlobalScopes()
            ->where('school_id', $request->user()->school_id)
            ->findOrFail($request->validated('class_room_id'));
        Gate::authorize('create', [AttendanceRecord::class, $classRoom]);

        $action->handle(
            $request->user()->school,
            $classRoom,
            $request->validated('attendance_date'),
            $request->validated('academic_period_id'),
            $request->validated('records'),
            $request->user()->id
        );

        return redirect()
            ->route($this->routePrefix($request).'.attendance.index')
            ->with('success', 'L’assiduité de la classe a été enregistrée.');
    }

    public function update(Request $request, AttendanceRecord $attendance, AuditService $audit): RedirectResponse
    {
        Gate::authorize('update', $attendance);
        $data = $request->validate([
            'status' => ['required', Rule::enum(AttendanceStatus::class)],
            'delay_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'reason' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['status'] === AttendanceStatus::Late->value && blank($data['delay_minutes'] ?? null)) {
            return back()->withErrors(['delay_minutes' => 'Indiquez le nombre de minutes de retard.']);
        }

        $before = $attendance->only(['status', 'delay_minutes', 'reason', 'note', 'justified_at', 'justified_by']);
        $attendance->fill([
            ...$data,
            'delay_minutes' => $data['status'] === AttendanceStatus::Late->value ? $data['delay_minutes'] : null,
            'justified_at' => $data['status'] === AttendanceStatus::Absent->value ? $attendance->justified_at : null,
            'justified_by' => $data['status'] === AttendanceStatus::Absent->value ? $attendance->justified_by : null,
            'recorded_by' => $request->user()->id,
        ])->save();

        $audit->log('attendance.updated', $attendance, [
            'before' => $before,
            'after' => $attendance->only(['status', 'delay_minutes', 'reason', 'note', 'justified_at', 'justified_by']),
        ]);

        return back()->with('success', 'Le relevé d’assiduité a été mis à jour.');
    }

    public function reviewJustification(Request $request, AttendanceRecord $attendance, AuditService $audit): RedirectResponse
    {
        Gate::authorize('justify', $attendance);
        $data = $request->validate([
            'justification_id' => ['nullable', 'integer'],
            'status' => ['required', Rule::in([
                AttendanceJustificationStatus::Approved->value,
                AttendanceJustificationStatus::Rejected->value,
            ])],
        ]);

        $justification = null;
        if (! empty($data['justification_id'])) {
            $justification = AttendanceJustification::query()
                ->where('attendance_record_id', $attendance->id)
                ->findOrFail($data['justification_id']);
            $justification->update([
                'status' => $data['status'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
        }

        $attendance->update([
            'justified_at' => $data['status'] === AttendanceJustificationStatus::Approved->value ? now() : null,
            'justified_by' => $data['status'] === AttendanceJustificationStatus::Approved->value ? $request->user()->id : null,
        ]);

        $audit->log('attendance.justified', $attendance, [
            'justification_id' => $justification?->id,
            'status' => $data['status'],
            'reason' => $justification?->reason,
        ]);

        return back()->with('success', $data['status'] === AttendanceJustificationStatus::Approved->value
            ? 'Absence justifiée.'
            : 'Justification refusée.');
    }

    public function studentHistory(Request $request, Student $student): Response
    {
        abort_unless((int) $student->school_id === (int) $request->user()->school_id, 404);
        if ($request->user()->isTeacher()) {
            abort_unless($student->class_id && $this->classIdsFor($request->user())?->contains($student->class_id), 403);
        }

        $records = AttendanceRecord::withoutGlobalScopes()
            ->where('school_id', $request->user()->school_id)
            ->where('student_id', $student->id)
            ->with(['recorder:id,name', 'justifications.parent:id,name', 'justifications.reviewer:id,name'])
            ->latest('attendance_date')
            ->paginate(30)
            ->withQueryString();

        $base = AttendanceRecord::withoutGlobalScopes()
            ->where('school_id', $request->user()->school_id)
            ->where('student_id', $student->id);

        return Inertia::render('Staff/Attendance/Student', [
            'student' => [
                'id' => $student->id,
                'name' => $student->full_name,
                'class_name' => $student->classRoom?->name,
                'matricule' => $student->matricule,
            ],
            'records' => $records,
            'stats' => [
                'absent' => (clone $base)->where('status', AttendanceStatus::Absent->value)->count(),
                'justified' => (clone $base)->where('status', AttendanceStatus::Absent->value)->whereNotNull('justified_at')->count(),
                'unjustified' => (clone $base)->where('status', AttendanceStatus::Absent->value)->whereNull('justified_at')->count(),
                'late' => (clone $base)->where('status', AttendanceStatus::Late->value)->count(),
                'delay_minutes' => (clone $base)->sum('delay_minutes'),
            ],
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    private function classesFor(User $user)
    {
        $classes = ClassRoom::query()->orderBy('name');
        $classIds = $this->classIdsFor($user);

        if ($classIds !== null) {
            $classes->whereIn('id', $classIds);
        }

        return $classes->get();
    }

    private function classIdsFor(User $user): ?Collection
    {
        if (! $user->isTeacher()) {
            return null;
        }

        return $user->teacher?->classes()->pluck('classes.id') ?? collect();
    }

    private function routePrefix(Request $request): string
    {
        return $request->user()->isAdmin()
            ? 'admin'
            : ($request->user()->isCenseur() ? 'censeur' : 'teacher');
    }
}
