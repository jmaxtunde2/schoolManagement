<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\AcademicPeriodRequest;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AcademicPeriodController extends Controller
{
    public function index(): Response
    {
        $periods = AcademicPeriod::query()
            ->with('academicYear:id,name,is_current')
            ->withCount(['evaluations', 'reportCards', 'attendanceRecords'])
            ->orderByDesc('academic_year_id')
            ->orderBy('position')
            ->get()
            ->map(fn (AcademicPeriod $period) => [
                'id' => $period->id,
                'academic_year_id' => $period->academic_year_id,
                'academic_year' => $period->academicYear?->name,
                'name' => $period->name,
                'position' => $period->position,
                'starts_at' => $period->starts_at?->format('Y-m-d'),
                'ends_at' => $period->ends_at?->format('Y-m-d'),
                'is_closed' => $period->is_closed,
                'evaluations_count' => $period->evaluations_count,
                'report_cards_count' => $period->report_cards_count,
                'attendance_count' => $period->attendance_records_count,
                'is_used' => $period->evaluations_count > 0
                    || $period->report_cards_count > 0
                    || $period->attendance_records_count > 0,
            ]);

        return Inertia::render('Admin/AcademicPeriods/Index', [
            'years' => AcademicYear::orderByDesc('name')->get(['id', 'name', 'is_current']),
            'periods' => $periods,
        ]);
    }

    public function store(AcademicPeriodRequest $request): RedirectResponse
    {
        AcademicPeriod::create($request->validated());

        return back()->with('success', 'Période scolaire créée.');
    }

    public function update(AcademicPeriodRequest $request, AcademicPeriod $academicPeriod): RedirectResponse
    {
        $academicPeriod->update($request->validated());

        return back()->with('success', 'Période scolaire mise à jour.');
    }

    /**
     * Suppression d'une période.
     *
     * Une période porteuse de notes, de bulletins ou d'absences n'est pas
     * supprimable : la supprimer détruirait des données scolaires déjà
     * enregistrées. On invite l'utilisateur à la clôturer.
     */
    public function destroy(AcademicPeriod $academicPeriod): RedirectResponse
    {
        if ($this->isUsed($academicPeriod)) {
            return back()->with(
                'error',
                'Cette période est rattachée à des évaluations, des bulletins ou des absences. Clôturez-la plutôt que de la supprimer.'
            );
        }

        $academicPeriod->delete();

        return back()->with('success', 'Période scolaire supprimée.');
    }

    /**
     * Clôture / réouverture d'une période.
     *
     * Une période close n'est plus utilisée pour générer de nouveaux
     * bulletins ; elle reste consultable et ses données sont conservées.
     */
    public function toggle(AcademicPeriod $academicPeriod): RedirectResponse
    {
        $closing = ! $academicPeriod->is_closed;

        $academicPeriod->update(['is_closed' => $closing]);

        return back()->with(
            'success',
            $closing
                ? 'Période close : elle n\'acceptera plus de nouveaux bulletins.'
                : 'Période rouverte.'
        );
    }

    private function isUsed(AcademicPeriod $period): bool
    {
        return $period->evaluations()->exists()
            || $period->reportCards()->exists()
            || $period->attendanceRecords()->exists();
    }
}
