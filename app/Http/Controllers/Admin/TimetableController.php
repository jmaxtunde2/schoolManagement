<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Timetable\TimetableRequest;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Emploi du temps — administration.
 *
 * Vue hebdomadaire (Lundi → Samedi) de l'année scolaire courante, avec création,
 * modification et suppression des créneaux. school_id n'est jamais lu dans la
 * requête : il vient du modèle (BelongsToSchool) et du policy.
 */
class TimetableController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Timetable::class);

        $academicYears = AcademicYear::orderByDesc('starts_on')->get(['id', 'name', 'is_current']);

        $selectedYearId = $request->integer('academic_year_id')
            ?: $academicYears->firstWhere('is_current', true)?->id
            ?: $academicYears->first()?->id;

        $slots = Timetable::with([
            'classRoom:id,name',
            'subject:id,name',
            'teacher.user:id,name,photo_path',
        ])
            ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
            ->when($request->filled('class_id'), fn ($q) => $q->where('class_id', $request->integer('class_id')))
            ->orderBy('day_of_week')
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Timetable $slot) => $this->present($slot));

        return Inertia::render('Admin/Timetables/Index', [
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
            'classes' => ClassRoom::orderBy('name')->get(['id', 'name']),
            'subjects' => Subject::orderBy('name')->get(['id', 'name']),
            'teachers' => Teacher::with('user:id,name,is_active')->get()
                ->filter(fn (Teacher $teacher) => $teacher->user !== null)
                ->map(fn (Teacher $teacher) => [
                    'id' => $teacher->id,
                    'name' => $teacher->user->name,
                    'is_active' => $teacher->user->is_active,
                ])
                ->sortBy('name')
                ->values(),
            'filters' => $request->only(['academic_year_id', 'class_id']),
            'canManage' => $request->user()->can('create', Timetable::class),
        ]);
    }

    public function store(TimetableRequest $request): RedirectResponse
    {
        Gate::authorize('create', Timetable::class);

        Timetable::create($request->validated());

        return back()->with('success', 'Créneau ajouté à l’emploi du temps.');
    }

    public function update(TimetableRequest $request, Timetable $timetable): RedirectResponse
    {
        Gate::authorize('update', $timetable);

        $timetable->update($request->validated());

        return back()->with('success', 'Créneau mis à jour.');
    }

    public function destroy(Timetable $timetable): RedirectResponse
    {
        Gate::authorize('delete', $timetable);

        $timetable->delete();

        return back()->with('success', 'Créneau supprimé.');
    }

    private function present(Timetable $slot): array
    {
        return [
            'id' => $slot->id,
            'day_of_week' => $slot->day_of_week,
            'day_label' => $slot->day_label,
            'starts_at' => $slot->formatTime($slot->starts_at),
            'ends_at' => $slot->formatTime($slot->ends_at),
            'time_range' => $slot->time_range,
            'room' => $slot->room,
            'class_id' => $slot->class_id,
            'class_name' => $slot->classRoom?->name,
            'subject_id' => $slot->subject_id,
            'subject_name' => $slot->subject?->name,
            'teacher_id' => $slot->teacher_id,
            'teacher_name' => $slot->teacher?->user?->name,
            'teacher_photo_url' => $slot->teacher?->user?->photoUrl(),
            'academic_year_id' => $slot->academic_year_id,
        ];
    }
}
