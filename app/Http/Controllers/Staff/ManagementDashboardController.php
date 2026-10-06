<?php

namespace App\Http\Controllers\Staff;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\StudentContribution;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\User;
use App\Services\Billing\BillingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tableau de bord des rôles de direction et de gestion.
 *
 * Le directeur et le comptable n'administrent pas l'établissement : ils le pilotent
 * en lecture. Une seule page sert les deux rôles, adaptée via `role`, ce qui évite
 * de dupliquer deux tableaux de bord quasi identiques.
 */
class ManagementDashboardController extends Controller
{
    public function __invoke(Request $request, BillingService $billing): Response
    {
        $school = $request->user()->school;
        $role = $request->user()->role;

        abort_unless($role === Role::Director || $role === Role::Accountant, 403);

        $currentYear = AcademicYear::where('is_current', true)->latest('id')->first();

        $payload = [
            'role' => $role->value,
            'role_label' => $role->label(),
            'school' => [
                'name' => $school->name,
                'short_name' => $school->settings?->short_name,
            ],
            'currentAcademicYear' => $currentYear?->only(['id', 'name']),
            'stats' => [
                'students' => Student::where('is_active', true)->count(),
                'teachers' => Teacher::count(),
                'classes' => ClassRoom::count(),
                'staff' => User::whereIn('role', array_map(
                    fn (Role $r) => $r->value,
                    Role::assignableStaff()
                ))->count(),
                'parents' => ParentGuardian::count(),
                'timetable_slots' => Timetable::when(
                    $currentYear,
                    fn ($q) => $q->where('academic_year_id', $currentYear->id)
                )->count(),
            ],
        ];

        if ($role === Role::Accountant) {
            $summary = $billing->summary($school);

            $payload['billing'] = [
                'summary' => $summary,
                'settings' => $billing->settings($school),
                'recent_contributions' => StudentContribution::query()
                    ->with('student:id,first_name,last_name')
                    ->latest('id')
                    ->take(8)
                    ->get()
                    ->map(fn (StudentContribution $contribution) => [
                        'id' => $contribution->id,
                        'student' => trim($contribution->student?->full_name ?? '—'),
                        'amount_due' => $contribution->amount_due,
                        'amount_paid' => $contribution->amount_paid,
                        'status' => $contribution->status,
                        'paid_at' => $contribution->paid_at?->toDateString(),
                    ]),
            ];
        }

        return Inertia::render('Staff/ManagementDashboard', $payload);
    }
}
