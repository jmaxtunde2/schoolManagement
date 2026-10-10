<?php

namespace App\Imports;

use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\School;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class StudentImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    protected School $school;
    protected array $results = ['created' => 0, 'updated' => 0, 'errors' => []];
    protected array $classCache = [];

    public function __construct(School $school)
    {
        $this->school = $school;
        $this->loadClassCache();
    }

    protected function loadClassCache(): void
    {
        $this->classCache = ClassRoom::where('school_id', $this->school->id)
            ->pluck('id', 'name')
            ->toArray();
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $data = $this->transformRow($row);

            if (! $data) {
                continue;
            }

            // Find class by name
            $className = $data['class_name'] ?? null;
            $classId = $className ? ($this->classCache[$className] ?? null) : null;

            if (! $classId && $className) {
                $this->results['errors'][] = "Classe '$className' introuvable pour l'élève {$data['first_name']} {$data['last_name']}";
                continue;
            }

        $student = Student::updateOrCreate(
            ['school_id' => $this->school->id, 'matricule' => $data['matricule']],
            array_merge($data, ['class_id' => $classId, 'matricule' => $data['matricule']])
        );

            if ($student->wasRecentlyCreated) {
                $this->results['created']++;
            } else {
                $this->results['updated']++;
            }
        }
    }

    protected function transformRow($row): ?array
    {
        if (! $firstName || ! $lastName) {
            return null;
        }

        $matricule = $this->getValue($row, ['matricule', 'student_number', 'numero_eleve', 'numero']);

        return [
            'school_id' => $this->school->id,
            'matricule' => $matricule ? trim((string) $matricule) : null,
            'first_name' => trim((string) $firstName),
            'last_name' => trim((string) $lastName),
            'gender' => $this->parseGender($this->getValue($row, ['gender', 'sexe', 'sexe_eleve'])),
            'birth_date' => $this->parseDate($this->getValue($row, ['birth_date', 'date_naissance', 'naissance'])),
            'birth_place' => $this->getValue($row, ['birth_place', 'lieu_naissance']),
            'address' => $this->getValue($row, ['address', 'adresse']),
            'phone' => $this->getValue($row, ['phone', 'telephone', 'tel']),
            'class_name' => $this->getValue($row, ['class', 'classe', 'class_name']),
        ];
    }

    protected function getValue($row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && $row[$key] !== '') {
                return $row[$key];
            }
        }
        return null;
    }

    protected function parseGender($value): ?string
    {
        if (! $value) {
            return null;
        }
        $value = strtolower(trim((string) $value));
        return match (true) {
            in_array($value, ['m', 'masculin', 'male', 'homme', 'garcon', 'garçon']) => 'M',
            in_array($value, ['f', 'feminin', 'féminin', 'female', 'fille']) => 'F',
            default => null,
        };
    }

    protected function parseDate($value): ?string
    {
        if (! $value) {
            return null;
        }
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'];
        foreach ($formats as $format) {
            try {
                return \Carbon\Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Exception) {
                continue;
            }
        }
        return null;
    }

    public function rules(): array
    {
        return [
            'student_number' => 'required_without:matricule|string|max:50',
            'matricule' => 'required_without:student_number|string|max:50',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'gender' => 'nullable|in:M,F',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'class' => 'nullable|string',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'student_number.required' => 'Le numéro d\'élève est obligatoire',
            'first_name.required' => 'Le prénom est obligatoire',
            'last_name.required' => 'Le nom est obligatoire',
        ];
    }

    public function getResults(): array
    {
        return $this->results;
    }
}