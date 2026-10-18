<?php

namespace App\Actions\Academic;

use App\Models\AcademicPeriod;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Student;
use App\Services\Academic\AcademicCalculationService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class EducMasterResultsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    private ?array $results = null;

    public function __construct(
        private ClassRoom $classRoom,
        private AcademicPeriod $period,
        private AcademicCalculationService $calculator,
        private School $school
    ) {}

    public function collection(): Collection
    {
        return collect($this->results()['students']);
    }

    public function headings(): array
    {
        $subjectNames = $this->subjectNames();

        return array_merge([
            'Matricule',
            'Numéro Table',
            'Nom',
            'Prénom(s)',
            'Date Naissance',
            'Sexe',
            'École',
            'Classe',
            'Année Scolaire',
            'Période',
        ], $subjectNames, [
            'Moyenne Générale',
            'Rang',
            'Total Élèves Classés',
            'Décision',
            'Absences',
            'Absences Justifiées',
            'Absences Non Justifiées',
            'Retards',
            'Minutes Retard',
        ]);
    }

    public function map($student): array
    {
        $subjectAverages = collect($student['subjects'])->keyBy('subject_name');

        $subjectNames = $this->subjectNames();
        $results = $this->results();

        $subjectScores = [];
        foreach ($subjectNames as $subjectName) {
            $subjectScores[] = $subjectAverages->get($subjectName)['average'] ?? '';
        }

        $studentModel = Student::find($student['student_id']);
        $birthDate = $studentModel?->birth_date?->format('d/m/Y') ?? '';
        $sex = $studentModel?->gender ?? '';

        $decision = $this->determineDecision($student['general_average']);

        return array_merge([
            $student['matricule'],
            $student['matricule'], // Numéro de table = matricule par défaut
            $studentModel?->last_name ?? '',
            $studentModel?->first_name ?? '',
            $birthDate,
            $sex,
            $this->school->name,
            $this->classRoom->name,
            $results['academic_year_name'],
            $results['period_name'],
        ], $subjectScores, [
            $student['general_average'] ?? '',
            $student['rank'] ?? '',
            $results['ranked_students'] ?? '',
            $decision,
            $student['attendance']['absences'] ?? 0,
            $student['attendance']['justified_absences'] ?? 0,
            $student['attendance']['unjustified_absences'] ?? 0,
            $student['attendance']['late_count'] ?? 0,
            $student['attendance']['delay_minutes'] ?? 0,
        ]);
    }

    public function title(): string
    {
        return 'Résultats EducMaster - '.$this->classRoom->name;
    }

    private function results(): array
    {
        return $this->results ??= $this->calculator->calculateClassResults(
            $this->classRoom,
            $this->period,
            $this->school->id,
        );
    }

    /**
     * @return array<int, string>
     */
    private function subjectNames(): array
    {
        return collect($this->results()['students'])
            ->flatMap(fn (array $student) => collect($student['subjects'])->pluck('subject_name'))
            ->unique()
            ->values()
            ->all();
    }

    private function determineDecision(?float $average): string
    {
        if ($average === null) {
            return 'Non classé';
        }

        // Standard béninois CEP : moyenne >= 10 = Admis, >= 8 = Repêchage, < 8 = Ajourné
        // Mais pour les résultats trimestriels/semestriels, on utilise une échelle adaptée
        if ($average >= 10) {
            return 'Admis';
        } elseif ($average >= 8) {
            return 'Repêchage';
        }

        return 'Ajourné';
    }
}
