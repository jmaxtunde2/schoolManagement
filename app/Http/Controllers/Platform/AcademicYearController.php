<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicYear\StoreAcademicYear;
use App\Http\Requests\AcademicYear\UpdateAcademicYear;
use App\Models\AcademicYear;
use App\Models\School;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AcademicYearController extends Controller
{
    public function index(Request $request, School $school): Response
    {
        $years = AcademicYear::query()
            ->where('school_id', $school->id)
            ->orderByDesc('starts_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Platform/Schools/AcademicYears/Index', [
            'school' => ['id' => $school->id, 'name' => $school->name, 'slug' => $school->slug],
            'years' => $years,
        ]);
    }

    public function store(StoreAcademicYear $request, School $school): RedirectResponse
    {
        $data = $request->validated();
        $data['school_id'] = $school->id;

        // Si on marque comme actuelle, désactiver les autres
        if ($data['is_current'] ?? false) {
            AcademicYear::where('school_id', $school->id)
                ->where('is_current', true)
                ->update(['is_current' => false]);
        }

        $year = AcademicYear::create($data);

        app(AuditService::class)->log('academic_year.created', $year, [
            'school_id' => $school->id,
            'by' => $request->user()->id,
        ]);

        return back()->with('success', "Année scolaire « {$year->name} » créée.");
    }

    public function update(UpdateAcademicYear $request, School $school, AcademicYear $academicYear): RedirectResponse
    {
        $this->authorizeForSchool($school, $academicYear);

        $data = $request->validated();

        // Si on marque comme actuelle, désactiver les autres
        if ($data['is_current'] ?? false) {
            AcademicYear::where('school_id', $school->id)
                ->where('is_current', true)
                ->where('id', '!=', $academicYear->id)
                ->update(['is_current' => false]);
        }

        $academicYear->update($data);

        app(AuditService::class)->log('academic_year.updated', $academicYear, [
            'school_id' => $school->id,
            'by' => $request->user()->id,
        ]);

        return back()->with('success', "Année scolaire « {$academicYear->name} » mise à jour.");
    }

    public function destroy(Request $request, School $school, AcademicYear $academicYear): RedirectResponse
    {
        $this->authorizeForSchool($school, $academicYear);

        $name = $academicYear->name;
        $academicYear->delete();

        app(AuditService::class)->log('academic_year.deleted', $academicYear, [
            'school_id' => $school->id,
            'name' => $name,
            'by' => $request->user()->id,
        ]);

        return back()->with('success', "Année scolaire « {$name} » supprimée.");
    }

    public function toggle(Request $request, School $school, AcademicYear $academicYear): RedirectResponse
    {
        $this->authorizeForSchool($school, $academicYear);

        $academicYear->update(['is_current' => ! $academicYear->is_current]);

        // Si activée, désactiver les autres
        if ($academicYear->is_current) {
            AcademicYear::where('school_id', $school->id)
                ->where('is_current', true)
                ->where('id', '!=', $academicYear->id)
                ->update(['is_current' => false]);
        }

        app(AuditService::class)->log('academic_year.toggled', $academicYear, [
            'school_id' => $school->id,
            'new_status' => $academicYear->is_current ? 'current' : 'not_current',
            'by' => $request->user()->id,
        ]);

        return back()->with('success', $academicYear->is_current
            ? "Année « {$academicYear->name} » définie comme actuelle."
            : "Année « {$academicYear->name} » retirée des actuelles.");
    }

    protected function authorizeForSchool(School $school, AcademicYear $academicYear): void
    {
        abort_unless($academicYear->school_id === $school->id, 403);
    }
}