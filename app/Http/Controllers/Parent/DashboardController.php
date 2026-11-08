<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ParentMessage;
use App\Models\StudentContribution;
use App\Models\Timetable;
use App\Services\ParentAccess\ParentAccessContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly ParentAccessContext $context) {}

    public function __invoke(Request $r): Response
    {
        $parent = $this->context->parent($r)->load(['students.classRoom']);
        $students = $parent->students;

        $contributions = StudentContribution::query()
            ->where('school_id', $parent->school_id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->groupBy('student_id');
        $hasFinance = $contributions->isNotEmpty();

        $due = (int) $contributions->flatten()->sum('amount_due');
        $paid = (int) $contributions->flatten()->sum('amount_paid');

        $children = $students->map(function ($student) use ($contributions) {
            $rows = $contributions->get($student->id, collect());
            $childDue = (int) $rows->sum('amount_due');
            $childPaid = (int) $rows->sum('amount_paid');

            return [
                'id' => $student->id,
                'name' => $student->full_name,
                'matricule' => $student->matricule,
                'class_name' => $student->classRoom?->name,
                'fee_due' => $childDue,
                'fee_paid' => $childPaid,
                'fee_remaining' => max(0, $childDue - $childPaid),
                'fee_status' => $rows->isEmpty()
                    ? null
                    : ($childPaid >= $childDue ? 'paid' : ($childPaid > 0 ? 'partial' : 'pending')),
            ];
        })->values();

        $recentMessages = ParentMessage::query()
            ->where('school_id', $parent->school_id)
            ->where('parent_id', $parent->id)
            ->latest()
            ->take(3)
            ->get()
            ->map(fn (ParentMessage $message) => [
                'id' => $message->id,
                'subject' => $message->subject,
                'is_read' => $message->isRead(),
                'created_at' => $message->created_at?->diffForHumans(),
            ])
            ->values();

        $academicYears = AcademicYear::query()
            ->where('school_id', $parent->school_id)
            ->orderByDesc('starts_on')
            ->get(['id', 'name', 'is_current']);

        $selectedYearId = $r->integer('academic_year_id')
            ?: $academicYears->firstWhere('is_current', true)?->id
            ?: $academicYears->first()?->id;

        $timetableData = [];
        foreach ($students as $student) {
            $slots = Timetable::where('school_id', $parent->school_id)
                ->where('class_id', $student->class_id)
                ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
                ->get()
                ->map(fn (Timetable $slot) => [
                    'id' => $slot->id,
                    'day_label' => $slot->day_label,
                    'starts_at' => $slot->formatTime($slot->starts_at),
                    'ends_at' => $slot->formatTime($slot->ends_at),
                    'time_range' => $slot->time_range,
                    'room_name' => $slot->room?->name,
                    'class_name' => $slot->classRoom?->name,
                    'subject_name' => $slot->subject?->name,
                    'teacher_name' => $slot->teacher?->user?->name,
                ]);

            $timetableData[$student->id] = [
                'slots' => $slots->count() > 0 ? $slots : [],
            ];
        }

        return Inertia::render('Parent/Dashboard', [
            'parent' => [
                'name' => $parent->name,
                'phone' => $parent->phone,
                'email' => $parent->email,
            ],
            'children' => $children,
            'hasFinance' => $hasFinance,
            'stats' => [
                'children' => $students->count(),
                'due' => $due,
                'paid' => $paid,
                'remaining' => max(0, $due - $paid),
                'fees_settled' => $due === 0 || $paid >= $due,
                'unread_messages' => ParentMessage::query()
                    ->where('school_id', $parent->school_id)
                    ->where('parent_id', $parent->id)
                    ->where('status', ParentMessage::STATUS_NEW)
                    ->count(),
            ],
            'recentMessages' => $recentMessages,
            'space' => $this->context->space($r),
            'timetable' => $timetableData,
        ]);
    }
}
