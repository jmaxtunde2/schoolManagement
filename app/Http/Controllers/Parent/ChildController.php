<?php

namespace App\Http\Controllers\Parent;

use App\Enums\AttendanceJustificationStatus;
use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\AttendanceJustification;
use App\Models\AttendanceRecord;
use App\Models\ReportCard;
use App\Models\Student;
use App\Services\AuditService;
use App\Services\ParentAccess\ParentAccessContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ChildController extends Controller
{
    public function __construct(private readonly ParentAccessContext $context) {}

    private function child(Request $request, Student $student): Student
    {
        $student->load('classRoom');

        $parent = $this->context->parent($request);
        $school = $this->context->school($request);

        abort_unless(
            (int) $student->school_id === (int) $school->id
            && $parent->students()->whereKey($student->id)->exists(),
            403
        );

        return $student;
    }

    public function show(Request $request, Student $student): Response
    {
        $student = $this->child($request, $student);
        $results = $student->results()
            ->whereHas('evaluation', fn ($query) => $query->where('status', 'validated'))
            ->with(['evaluation.subject', 'evaluation.academicPeriod'])
            ->latest()
            ->get();

        return Inertia::render('Parent/Child', [
            'student' => [
                'id' => $student->id,
                'name' => $student->full_name,
                'matricule' => $student->matricule,
                'class_name' => $student->classRoom?->name,
            ],
            'results' => $results->map(fn ($result) => [
                'id' => $result->id,
                'subject' => $result->evaluation->subject->name,
                'title' => $result->evaluation->title,
                'period' => $result->evaluation->academicPeriod?->name,
                'score' => $result->score,
                'max_score' => $result->evaluation->max_score,
                'coefficient' => $result->evaluation->coefficient,
                'date' => $result->evaluation->evaluation_date->format('d/m/Y'),
            ]),
            'space' => $this->context->space($request),
        ]);
    }

    public function attendance(Request $request, Student $student): Response
    {
        $student = $this->child($request, $student);
        $baseQuery = AttendanceRecord::withoutGlobalScopes()
            ->where('school_id', $this->context->school($request)->id)
            ->where('student_id', $student->id);

        $records = (clone $baseQuery)
            ->with(['recorder:id,name', 'justifications.parent:id,name', 'justifications.reviewer:id,name'])
            ->latest('attendance_date')
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
                'justifications' => $record->justifications->map(fn (AttendanceJustification $justification) => [
                    'id' => $justification->id,
                    'reason' => $justification->reason,
                    'status' => $justification->status->value,
                    'reviewed_at' => $justification->reviewed_at?->toDateTimeString(),
                ])->values(),
            ]);

        return Inertia::render('Parent/Attendance', [
            'student' => [
                'id' => $student->id,
                'name' => $student->full_name,
                'class_name' => $student->classRoom?->name,
                'matricule' => $student->matricule,
            ],
            'records' => $records,
            'stats' => [
                'absent' => (clone $baseQuery)->where('status', AttendanceStatus::Absent->value)->count(),
                'justified' => (clone $baseQuery)->where('status', AttendanceStatus::Absent->value)->whereNotNull('justified_at')->count(),
                'late' => (clone $baseQuery)->where('status', AttendanceStatus::Late->value)->count(),
                'delay_minutes' => (clone $baseQuery)->sum('delay_minutes'),
                'permission' => (clone $baseQuery)->where('status', AttendanceStatus::Permission->value)->count(),
            ],
            'space' => $this->context->space($request),
        ]);
    }

    public function reportCards(Request $request, Student $student): Response
    {
        $student = $this->child($request, $student);
        $reportCards = ReportCard::withoutGlobalScopes()
            ->where('school_id', $this->context->school($request)->id)
            ->where('student_id', $student->id)
            ->where('status', 'published')
            ->with(['academicYear:id,name', 'period:id,name', 'classRoom:id,name'])
            ->orderByDesc('academic_year_id')
            ->orderByDesc('academic_period_id')
            ->orderByDesc('version')
            ->get()
            ->map(fn (ReportCard $reportCard) => [
                'id' => $reportCard->id,
                'version' => $reportCard->version,
                'academic_year' => $reportCard->academicYear?->name,
                'period' => $reportCard->period?->name,
                'class_name' => $reportCard->classRoom?->name,
                'general_average' => (float) $reportCard->general_average,
                'rank' => $reportCard->rank,
                'total_students' => $reportCard->total_students,
                'published_at' => $reportCard->published_at?->format('d/m/Y'),
            ]);

        return Inertia::render('Parent/ReportCards/Index', [
            'student' => [
                'id' => $student->id,
                'name' => $student->full_name,
                'class_name' => $student->classRoom?->name,
                'matricule' => $student->matricule,
            ],
            'reportCards' => $reportCards,
            'space' => $this->context->space($request),
        ]);
    }

    public function justify(
        Request $request,
        AttendanceRecord $attendance,
        AuditService $audit
    ): RedirectResponse {
        $parent = $this->context->parent($request);

        if ($this->context->isParentAccess($request)) {
            $school = $this->context->school($request);

            abort_unless(
                (int) $attendance->school_id === (int) $school->id
                && $parent->students()->whereKey($attendance->student_id)->exists()
                && $attendance->status === AttendanceStatus::Absent
                && $attendance->justified_at === null
                && ! $attendance->justifications()
                    ->where('status', AttendanceJustificationStatus::Approved->value)
                    ->exists(),
                403
            );
        } else {
            Gate::authorize('submitJustification', $attendance);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        if ($attendance->justifications()
            ->where('parent_id', $parent->id)
            ->where('status', AttendanceJustificationStatus::Pending->value)
            ->exists()) {
            return back()->withErrors([
                'reason' => 'Une justification est déjà en attente pour cette absence.',
            ]);
        }

        $justification = $attendance->justifications()->create([
            'parent_id' => $parent->id,
            'reason' => $data['reason'],
            'status' => AttendanceJustificationStatus::Pending->value,
        ]);

        $audit->log('attendance.justification_submitted', $attendance, [
            'justification_id' => $justification->id,
            'student_id' => $attendance->student_id,
            'reason' => $justification->reason,
        ]);

        return back()->with('success', 'Justification envoyée à l’établissement.');
    }
}
