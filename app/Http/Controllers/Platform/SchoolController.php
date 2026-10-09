<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SchoolController extends Controller
{
    public function index(Request $request): Response
    {
        $schools = School::query()
            ->withCount(['users', 'students', 'classRooms'])
            ->when($request->query('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->query('status'), fn ($q, $s) => $q->where('is_active', $s === 'active'))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Platform/Schools/Index', [
            'schools' => $schools,
            'filters' => [
                'search' => $request->query('search'),
                'status' => $request->query('status'),
            ],
        ]);
    }

    public function show(School $school): Response
    {
        $school->loadCount(['users', 'students', 'teachers', 'classRooms', 'academicYears']);

        $admins = User::query()
            ->where('school_id', $school->id)
            ->where('role', 'admin')
            ->get(['id', 'name', 'email', 'is_active']);

        $license = $school->licenses()->latest()->first();
        $latestPayments = $school->payments()->latest()->take(5)->get(['id', 'amount', 'status', 'reference', 'created_at']);

        return Inertia::render('Platform/Schools/Show', [
            'school' => [
                'id' => $school->id,
                'name' => $school->name,
                'slug' => $school->slug,
                'is_active' => $school->is_active,
                'created_at' => $school->created_at->format('d/m/Y'),
                'counts' => [
                    'users' => $school->users_count,
                    'students' => $school->students_count,
                    'teachers' => $school->teachers_count,
                    'classes' => $school->class_rooms_count,
                    'academic_years' => $school->academic_years_count,
                ],
            ],
            'admins' => $admins,
            'license' => $license ? [
                'id' => $license->id,
                'type' => $license->type->value,
                'type_label' => $license->type->label(),
                'status' => $license->status->value,
                'status_label' => $license->status->label(),
                'starts_at' => $license->starts_at?->format('d/m/Y'),
                'ends_at' => $license->ends_at?->format('d/m/Y'),
                'amount' => $license->amount,
                'payment_reference' => $license->payment_reference,
                'is_expired' => $license->isExpired(),
                'days_remaining' => $license->daysRemaining(),
            ] : null,
            'latestPayments' => $latestPayments->map(fn ($p) => [
                'id' => $p->id,
                'amount' => $p->amount,
                'status' => $p->status->value,
                'status_label' => $p->status->label(),
                'reference' => $p->reference,
                'created_at' => $p->created_at->format('d/m/Y H:i'),
            ]),
        ]);
    }

    public function toggle(School $school, Request $request): RedirectResponse
    {
        $school->update(['is_active' => ! $school->is_active]);

        app(AuditService::class)->log('school.toggled', $school, [
            'new_status' => $school->is_active ? 'active' : 'suspended',
            'by' => $request->user()->id,
        ]);

        return back()->with('success', $school->is_active
            ? "École « {$school->name} » réactivée."
            : "École « {$school->name} » suspendue.");
    }

    public function destroy(School $school, Request $request): RedirectResponse
    {
        $name = $school->name;
        $school->delete();

        app(AuditService::class)->log('school.deleted', $school, [
            'name' => $name,
            'by' => $request->user()->id,
        ]);

        return redirect()->route('platform.schools.index')
            ->with('success', "École « {$name} » supprimée.");
    }
}