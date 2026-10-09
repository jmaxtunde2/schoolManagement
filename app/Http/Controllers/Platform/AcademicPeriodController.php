<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicPeriod\StoreAcademicPeriod;
use App\Http\Requests\AcademicPeriod\UpdateAcademicPeriod;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AcademicPeriodController extends Controller
{
    public function index(Request $request, School $school): Response
    {
        $periods = AcademicPeriod::query()
            ->where('school_id', $school->id)
            ->with('academicYear:id,name')
            ->orderBy('academic_year_id')
            ->orderBy('starts_at')
            ->paginate(20)
            ->withQueryString();

        $years = AcademicYear::where('school_id', $school->id)
            ->orderByDesc('starts_at')
            ->get(['id', 'name']);

        return Inertia::render('Platform/Schools/AcademicPeriods/Index', [
            'school' => ['id' => $school->id, 'name' => $school->name, 'slug' => $school->slug],
            'periods' => $periods,
            'years' => $years,
        ]);
    }

    public function store(StoreAcademicPeriod $request, School $school): RedirectResponse
    {
        $data = $request->validated();
        $data['school_id'] = $school->id;

        $period = AcademicPeriod::create($data);

        app(AuditService::class)->log('academic_period.created', $period, [
            'school_id' => $school->id,
            'by' => $request->user()->id,
        ]);

        return back()->with('success', "Période « {$period->name} » créée.");
    }

    public function update(UpdateAcademicPeriod $request, School $school, AcademicPeriod $academicPeriod): RedirectResponse
    {
        $this->authorizeForSchool($school, $academicPeriod);

        $academicPeriod->update($request->validated());

        app(AuditService::class)->log('academic_period.updated', $academicPeriod, [
            'school_id' => $school->id,
            'by' => $request->user()->id,
        ]);

        return back()->with('success', "Période « {$academicPeriod->name} » mise à jour.");
    }

    public function destroy(Request $request, School $school, AcademicPeriod $academicPeriod): RedirectResponse
    {
        $this->authorizeForSchool($school, $academicPeriod);

        $name = $academicPeriod->name;
        $academicPeriod->delete();

        app(AuditService::class)->log('academic_period.deleted', $academicPeriod, [
            'school_id' => $school->id,
            'name' => $name,
            'by' => $request->user()->id,
        ]);

        return back()->with('success', "Période « {$name} » supprimée.");
    }

    public function toggle(Request $request, School $school, AcademicPeriod $academicPeriod): RedirectResponse
    {
        $this->authorizeForSchool($school, $academicPeriod);

        $academicPeriod->update(['is_active' => ! $academicPeriod->is_active]);

        app(AuditService::class)->log('academic_period.toggled', $academicPeriod, [
            'school_id' => $school->id,
            'new_status' => $academicPeriod->is_active ? 'active' : 'inactive',
            'by' => $request->user()->id,
        ]);

        return back()->with('success', $academicPeriod->is_active
            ? "Période « {$academicPeriod->name} » activée."
            : "Période « {$academicPeriod->name} » désactivée.");
    }

    protected function authorizeForSchool(School $school, AcademicPeriod $academicPeriod): void
    {
        abort_unless($academicPeriod->school_id === $school->id, 403);
    }
}