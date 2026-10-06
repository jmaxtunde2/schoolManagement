<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Emploi du temps d'un enfant, vu par son parent.
 *
 * Le lien parent ↔ élève est vérifié explicitement : un identifiant d'élève
 *forceable dans l'URL appartenant à un autre enfant du même établissement est
 * refusé, et un élève d'une autre école est invisible (SchoolScope).
 */
class TimetableController extends Controller
{
    public function __invoke(Request $request, Student $student): Response
    {
        $guardian = $request->user()->parentGuardian;

        abort_if(! $guardian, 403, "Aucun espace parent n'est associé à ce compte.");

        abort_unless(
            $guardian->students()->whereKey($student->id)->exists(),
            403,
            "Cet élève n'est pas rattaché à votre espace parent."
        );

        $academicYears = AcademicYear::orderByDesc('starts_on')->get(['id', 'name', 'is_current']);

        $selectedYearId = $request->integer('academic_year_id')
            ?: $academicYears->firstWhere('is_current', true)?->id
            ?: $academicYears->first()?->id;

        $slots = Timetable::with([
            'classRoom:id,name',
            'subject:id,name',
            'teacher.user:id,name',
        ])
            ->where('class_id', $student->class_id)
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
                'teacher_name' => $slot->teacher?->user?->name,
            ]);

        return Inertia::render('Parent/Timetable', [
            'student' => [
                'id' => $student->id,
                'name' => $student->full_name,
                'class_name' => $student->classRoom?->name,
                'matricule' => $student->matricule,
                'photo_url' => $student->photo_url,
            ],
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
