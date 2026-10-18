<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Attendance\BuildRecentIncidents;
use App\Actions\Finance\BuildFinanceSummary;
use App\Actions\Pilotage\BuildPilotageDashboard;
use App\Actions\Students\BuildGenderDistribution;
use App\Actions\Timetable\BuildTimetableBrief;
use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\Expense;
use App\Models\Notification;
use App\Models\Sale;
use App\Models\Student;
use App\Models\StudentContribution;
use App\Models\Teacher;
use App\Services\Assistant\AssistantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, BuildTimetableBrief $brief, BuildRecentIncidents $incidents, BuildGenderDistribution $genderDistribution, BuildFinanceSummary $financeSummary, BuildPilotageDashboard $pilotage): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'pilotage' => $pilotage->handle(AssistantContext::for($request->user()), 'admin'),
            'stats' => [
                'students' => Student::count(),
                'teachers' => Teacher::count(),
                'classes' => ClassRoom::count(),
                'evaluations' => Evaluation::count(),
                'notifications_sent' => Notification::whereIn('status', ['sent', 'delivered'])->count(),
                'notifications_failed' => Notification::where('status', 'failed')->count(),
            ],
            'recentEvaluations' => Evaluation::with(['classRoom:id,name', 'subject:id,name', 'teacher.user:id,name'])
                ->latest('evaluation_date')->take(5)->get(),
            'routePrefix' => 'admin',
            'timetableBrief' => $brief->handle(),
            'recentIncidents' => $incidents->handle(10),
            'genderDistribution' => $genderDistribution->handle(),
            'finance' => true,
            'financeSummary' => $financeSummary->handle(),
            'recentExpenses' => Expense::query()
                ->with('creator:id,name')
                ->latest('expense_date')->latest('id')->take(5)->get()
                ->map(fn (Expense $expense) => [
                    'id' => $expense->id,
                    'reference' => $expense->reference,
                    'label' => $expense->label,
                    'category_label' => $expense->category->label(),
                    'amount' => $expense->amount,
                    'expense_date' => $expense->expense_date->toDateString(),
                    'status' => $expense->status->value,
                    'status_label' => $expense->status->label(),
                    'creator_name' => $expense->creator?->name,
                ]),
            'recentSales' => Sale::query()
                ->with('creator:id,name')
                ->latest('sold_at')->latest('id')->take(5)->get()
                ->map(fn (Sale $sale) => [
                    'id' => $sale->id,
                    'item' => $sale->item,
                    'category_label' => $sale->category->label(),
                    'quantity' => $sale->quantity,
                    'total' => $sale->total,
                    'sold_at' => $sale->sold_at->toDateString(),
                    'creator_name' => $sale->creator?->name,
                ]),
            'recentContributions' => StudentContribution::query()
                ->with(['student:id,first_name,last_name,matricule', 'academicYear:id,name'])
                ->latest('id')->take(5)->get()
                ->map(fn (StudentContribution $contribution) => [
                    'id' => $contribution->id,
                    'student' => trim($contribution->student?->full_name ?? '—'),
                    'academic_year' => $contribution->academicYear?->name,
                    'amount_due' => $contribution->amount_due,
                    'amount_paid' => $contribution->amount_paid,
                    'status' => (int) $contribution->amount_paid >= (int) $contribution->amount_due
                        ? 'paid'
                        : ((int) $contribution->amount_paid > 0 ? 'partial' : 'pending'),
                ]),
        ]);
    }
}
