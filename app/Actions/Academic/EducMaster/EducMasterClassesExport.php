<?php

namespace App\Actions\Academic\EducMaster;

use App\Models\ClassRoom;
use App\Models\School;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Export de la liste des classes au format Excel pour EducMaster.
 */
class EducMasterClassesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    /**
     * @param  array<int, int>|null  $allowedClassIds
     */
    public function __construct(
        private School $school,
        private ?array $allowedClassIds = null,
    ) {}

    public function collection(): Collection
    {
        return ClassRoom::withoutGlobalScopes()
            ->where('school_id', $this->school->id)
            ->when($this->allowedClassIds !== null, fn ($query) => $query->whereIn('id', $this->allowedClassIds))
            ->withCount([
                'students',
                'subjects',
                'students as active_students_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Classe',
            'Niveau',
            'Capacité',
            'Effectif total',
            'Effectif actif',
            'Nombre de matières',
        ];
    }

    public function map($classRoom): array
    {
        return [
            $classRoom->name,
            $classRoom->level ?? '',
            $classRoom->capacity ?? '',
            $classRoom->students_count ?? 0,
            $classRoom->active_students_count ?? 0,
            $classRoom->subjects_count ?? 0,
        ];
    }

    public function title(): string
    {
        return 'Classes';
    }
}
