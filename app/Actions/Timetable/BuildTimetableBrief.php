<?php

namespace App\Actions\Timetable;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\SchoolSetting;
use App\Models\Timetable;
use Illuminate\Support\Carbon;

/**
 * Résumé de l'exécution de l'emploi du temps du jour.
 *
 * Sert les tableaux de bord de direction : à l'ouverture, on veut voir ce qui
 * est prévu « ce matin » et « ce soir », et distinguer d'un coup d'œil les cours
 * déjà passés, en cours d'exécution et à venir.
 *
 * Le découpage matin / soir se fait sur l'heure de début (12:00). Le statut de
 * chaque cours est calculé à partir de l'heure courante dans le fuseau horaire
 * de l'établissement.
 */
class BuildTimetableBrief
{
    /** Heure de bascule entre le matin et le soir. */
    public const EVENING_STARTS_AT = '12:00';

    public function handle(?Carbon $now = null, ?iterable $classIds = null, ?string $timezone = null): array
    {
        $timezone ??= $this->resolveTimezone($classIds);
        $now ??= now($timezone);
        $classIds = $classIds === null ? null : collect($classIds)->map(fn ($id) => (int) $id)->all();
        $currentTime = $now->format('H:i');
        $dayOfWeek = (int) $now->isoWeekday(); // 1 = lundi … 6 = samedi, 7 = dimanche
        $isSchoolDay = array_key_exists($dayOfWeek, Timetable::days());

        $year = AcademicYear::where('is_current', true)->latest('id')->first();

        $slots = collect();

        if ($isSchoolDay) {
            $slots = Timetable::with([
                'classRoom:id,name',
                'subject:id,name',
                'teacher.user:id,name',
                'room:id,name',
            ])
                ->when($classIds !== null, fn ($query) => $query->whereIn('class_id', $classIds))
                ->when($year, fn ($query) => $query->where('academic_year_id', $year->id))
                ->where('day_of_week', $dayOfWeek)
                ->orderBy('starts_at')
                ->get()
                ->map(fn (Timetable $slot) => $this->present($slot, $currentTime));
        }

        $morning = $slots
            ->filter(fn (array $slot) => $slot['starts_at'] < self::EVENING_STARTS_AT)
            ->values();
        $evening = $slots
            ->filter(fn (array $slot) => $slot['starts_at'] >= self::EVENING_STARTS_AT)
            ->values();

        return [
            'date' => $now->toDateString(),
            'day_label' => Timetable::days()[$dayOfWeek] ?? 'Dimanche',
            'is_school_day' => $isSchoolDay,
            'current_time' => $currentTime,
            'timezone' => $timezone,
            'academic_year' => $year?->only(['id', 'name']),
            'total' => $slots->count(),
            'in_progress_count' => $slots->where('status', 'current')->count(),
            'morning' => $this->group('Matin', $morning),
            'evening' => $this->group('Soir', $evening),
        ];
    }

    private function resolveTimezone(?iterable $classIds): string
    {
        if ($classIds === null) {
            return config('app.timezone');
        }

        $schoolId = ClassRoom::whereIn('id', $classIds)
            ->value('school_id');

        if (! $schoolId) {
            return config('app.timezone');
        }

        return SchoolSetting::where('school_id', $schoolId)
            ->value('timezone') ?? config('app.timezone');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array>  $slots
     */
    private function group(string $label, $slots): array
    {
        return [
            'label' => $label,
            'count' => $slots->count(),
            'in_progress_count' => $slots->where('status', 'current')->count(),
            'slots' => $slots->all(),
        ];
    }

    private function present(Timetable $slot, string $currentTime): array
    {
        $startsAt = $slot->formatTime($slot->starts_at);
        $endsAt = $slot->formatTime($slot->ends_at);

        if ($currentTime >= $endsAt) {
            $status = 'past';
        } elseif ($currentTime >= $startsAt) {
            $status = 'current';
        } else {
            $status = 'upcoming';
        }

        return [
            'id' => $slot->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'time_range' => $slot->time_range,
            'subject_name' => $slot->subject?->name,
            'class_name' => $slot->classRoom?->name,
            'teacher_name' => $slot->teacher?->user?->name,
            'room_name' => $slot->room?->name,
            'status' => $status,
        ];
    }
}