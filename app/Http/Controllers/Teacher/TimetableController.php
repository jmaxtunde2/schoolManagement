<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Emploi du temps de l'enseignant.
 *
 * Un enseignant ne voit que ses propres créneaux : le filtre porte sur
 * `teacher_id`, donc aucun paramètre `teacher_id` reçu dans l'URL n'ouvre
 * la charge d'un collègue.
 */
class TimetableController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Gate::authorize('viewAny', Timetable::class);

        $teacher = $request->user()->teacher;

        abort_if(! $teacher, 403, "Aucun profil enseignant n'est associé à ce compte.");

        $academicYears = AcademicYear::orderByDesc('starts_on')->get(['id', 'name', 'is_current']);

        $selectedYearId = $request->integer('academic_year_id')
            ?: $academicYears->firstWhere('is_current', true)?->id
            ?: $academicYears->first()?->id;

        $slots = Timetable::with(['classRoom:id,name', 'subject:id,name'])
            ->where('teacher_id', $teacher->id)
            ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
            ->orderBy('day_of_week')
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Timetable $slot) => [
                'id' => $slot->id,
                'day_of_week' => $slot->day_of_week,
                'day_label' => $slot->day_label,
                'starts_at' => $slot->formatTime($slot->starts_at),
                'ends_at' => $slot->formatTime($slot->ends_at),
                'time_range' => $slot->time_range,
                'room' => $slot->room,
                'class_name' => $slot->classRoom?->name,
                'subject_name' => $slot->subject?->name,
            ]);

        return Inertia::render('Teacher/Timetable', [
            'slots' => $slots,
            'days' => collect(Timetable::days())->map(fn ($label, $value) => [
                'value' => $value,
                'label' => $label,
            ])->values(),
            'academicYears' => $academicYears->map(fn ($year) => [
                'id' => $year->id,
                'name' => $year->name,
                'is_current' => $year->is_current,
            ])->values(),
            'selectedAcademicYearId' => $selectedYearId,
        ]);
    }
}
