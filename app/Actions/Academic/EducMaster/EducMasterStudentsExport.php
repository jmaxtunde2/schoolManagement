<?php

namespace App\Actions\Academic\EducMaster;

use App\Models\School;
use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Export de la liste des élèves au format Excel pour EducMaster.
 */
class EducMasterStudentsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    /**
     * @param  array<int, int>|null  $allowedClassIds  Restriction de périmètre (directeur : primaire). null = toutes.
     */
    public function __construct(
        private School $school,
        private ?int $classId = null,
        private ?array $allowedClassIds = null,
    ) {}

    public function collection(): Collection
    {
        return Student::withoutGlobalScopes()
            ->where('school_id', $this->school->id)
            ->when($this->classId, fn ($query) => $query->where('class_id', $this->classId))
            ->when($this->allowedClassIds !== null, fn ($query) => $query->whereIn('class_id', $this->allowedClassIds))
            ->with([
                'classRoom:id,name,level',
                'guardians:id,name,phone',
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Matricule',
            'Nom',
            'Prénom(s)',
            'Date de naissance',
            'Sexe',
            'Classe',
            'Niveau',
            'Statut',
            'Tuteur',
            'Téléphone tuteur',
        ];
    }

    public function map($student): array
    {
        $guardian = $student->guardians->first();

        return [
            $student->matricule,
            $student->last_name,
            $student->first_name,
            $student->birth_date?->format('d/m/Y') ?? '',
            $student->gender,
            $student->classRoom?->name ?? '',
            $student->classRoom?->level ?? '',
            $student->is_active ? 'Actif' : 'Inactif',
            $guardian?->name ?? '',
            $guardian?->phone ?? '',
        ];
    }

    public function title(): string
    {
        return 'Élèves';
    }
}
