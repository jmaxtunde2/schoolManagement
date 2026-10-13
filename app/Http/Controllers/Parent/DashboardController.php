<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\ParentMessage;
use App\Models\StudentContribution;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $r): Response
    {
        $parent = $r->user()->parentGuardian()->with(['students.classRoom'])->firstOrFail();
        $students = $parent->students;

        $contributions = StudentContribution::query()
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->groupBy('student_id');

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

        return Inertia::render('Parent/Dashboard', [
            'parent' => [
                'name' => $parent->name,
                'phone' => $parent->phone,
                'email' => $parent->email,
            ],
            'children' => $children,
            'stats' => [
                'children' => $students->count(),
                'due' => $due,
                'paid' => $paid,
                'remaining' => max(0, $due - $paid),
                'fees_settled' => $due === 0 || $paid >= $due,
                'unread_messages' => ParentMessage::query()
                    ->where('parent_id', $parent->id)
                    ->where('status', ParentMessage::STATUS_NEW)
                    ->count(),
            ],
            'recentMessages' => $recentMessages,
        ]);
    }
}
