<?php

namespace App\Services\Assistant;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\ClassRoom;
use App\Models\Student;
use Illuminate\Support\Carbon;

/**
 * Assiduité : synthèse des absences/retards et élèves à surveiller.
 *
 * Les requêtes sont explicitement bornées au `school_id` du contexte et, pour
 * le directeur primaire, aux classes du primaire.
 */
class SchoolAttendanceService
{
    public function __construct(private AssistantContext $context) {}

    /**
     * @return array{absences: int, retards: int, delay_minutes: int}
     */
    public function summary(Carbon $from, Carbon $to): array
    {
        $records = AttendanceRecord::query()
            ->whereIn('status', [AttendanceStatus::Absent->value, AttendanceStatus::Late->value])
            ->whereDate('attendance_date', '>=', $from->toDateString())
            ->whereDate('attendance_date', '<=', $to->toDateString())
            ->when(
                $this->context->allowedClassIds !== null,
                fn ($query) => $query->whereIn('class_room_id', $this->context->allowedClassIds),
            )
            ->get(['status', 'delay_minutes']);

        return [
            'absences' => $records->where('status', AttendanceStatus::Absent)->count(),
            'retards' => $records->where('status', AttendanceStatus::Late)->count(),
            'delay_minutes' => (int) $records->sum('delay_minutes'),
        ];
    }

    /**
     * Élèves ayant accumulé au moins `$min` absences sur la période.
     *
     * @return array<int, array{student: string, class: string, absences: int}>
     */
    public function repeatedAbsences(int $min, Carbon $from, Carbon $to): array
    {
        $counts = AttendanceRecord::query()
            ->where('status', AttendanceStatus::Absent->value)
            ->whereDate('attendance_date', '>=', $from->toDateString())
            ->whereDate('attendance_date', '<=', $to->toDateString())
            ->when(
                $this->context->allowedClassIds !== null,
                fn ($query) => $query->whereIn('class_room_id', $this->context->allowedClassIds),
            )
            ->selectRaw('student_id, count(*) as total')
            ->groupBy('student_id')
            ->havingRaw('count(*) >= ?', [$min])
            ->orderByDesc('total')
            ->get();

        if ($counts->isEmpty()) {
            return [];
        }

        $students = Student::withoutGlobalScopes()
            ->where('school_id', $this->context->schoolId)
            ->whereIn('id', $counts->pluck('student_id'))
            ->get(['id', 'first_name', 'last_name', 'class_id'])
            ->keyBy('id');

        $classes = ClassRoom::withoutGlobalScopes()
            ->where('school_id', $this->context->schoolId)
            ->whereIn('id', $students->pluck('class_id')->filter()->all())
            ->pluck('name', 'id');

        return $counts
            ->map(function ($row) use ($students, $classes) {
                $student = $students->get($row->student_id);

                return [
                    'student' => $student?->full_name ?? '—',
                    'class' => $classId = $student?->class_id
                        ? ($classes[$student->class_id] ?? '—')
                        : '—',
                    'absences' => (int) $row->total,
                ];
            })
            ->values()
            ->all();
    }
}
