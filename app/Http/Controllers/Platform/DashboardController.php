<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentContribution;
use App\Services\Billing\BillingService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(BillingService $billing)
    {
        $schools = School::query()->withCount(['users'])->get();
        $paid = StudentContribution::withoutGlobalScopes()->where('status', 'paid')->count();
        $collected = (int) StudentContribution::withoutGlobalScopes()->sum('amount_paid');
        $coriyase = 0;
        $schoolShare = 0;
        foreach ($schools as $school) {
            $summary = $billing->summary($school);
            $coriyase += $summary['coriyase_share'];
            $schoolShare += $summary['school_share'];
        }

return Inertia::render('Platform/Dashboard', ['stats' => ['schools' => $schools->count(), 'active_schools' => $schools->where('is_active', true)->count(), 'students' => Student::withoutGlobalScopes()->count(), 'paid_contributions' => $paid, 'collected' => $collected, 'coriyase_share' => $coriyase, 'school_share' => $schoolShare, 'notifications' => Notification::withoutGlobalScopes()->count()], 'schools' => $schools->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'active' => $s->is_active, 'users_count' => $s->users_count])]);
    }
}
