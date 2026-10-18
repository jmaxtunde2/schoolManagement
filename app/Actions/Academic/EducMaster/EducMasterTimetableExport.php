<?php

namespace App\Actions\Academic\EducMaster;

use App\Models\School;
use App\Models\Timetable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Export de l'emploi du temps au format Excel pour EducMaster.
 */
class EducMasterTimetableExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    /**
     * @param  array<int, int>|null  $allowedClassIds
     */
    public function __construct(
        private School $school,
        private ?int $academicYearId = null,
        private ?int $classId = null,
        private ?array $allowedClassIds = null,
    ) {}

    public function collection(): Collection
    {
        return Timetable::withoutGlobalScopes()
            ->where('school_id', $this->school->id)
            ->when($this->academicYearId, fn ($query) => $query->where('academic_year_id', $this->academicYearId))
            ->when($this->classId, fn ($query) => $query->where('class_id', $this->classId))
            ->when($this->allowedClassIds !== null, fn ($query) => $query->whereIn('class_id', $this->allowedClassIds))
            ->with([
                'classRoom:id,name',
                'subject:id,name',
                'teacher.user:id,name',
                'room:id,name',
                'academicYear:id,name',
            ])
            ->get()
            ->sortBy(fn (Timetable $timetable) => [
                (string) $timetable->classRoom?->name,
                (int) $timetable->day_of_week,
                (string) $timetable->starts_at,
            ])
            ->values();
    }

    public function headings(): array
    {
        return [
            'Classe',
            'Jour',
            'Heure début',
            'Heure fin',
            'Matière',
            'Enseignant',
            'Salle',
            'Année scolaire',
        ];
    }

    public function map($timetable): array
    {
        return [
            $timetable->classRoom?->name ?? '',
            $timetable->day_label,
            $timetable->formatTime($timetable->starts_at),
            $timetable->formatTime($timetable->ends_at),
            $timetable->subject?->name ?? '',
            $timetable->teacher?->user?->name ?? '',
            $timetable->room?->name ?? '',
            $timetable->academicYear?->name ?? '',
        ];
    }

    public function title(): string
    {
        return 'Emploi du temps';
    }
}
