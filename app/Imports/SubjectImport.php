<?php

namespace App\Imports;

use App\Models\Subject;
use App\Models\School;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class SubjectImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    protected School $school;
    protected array $results = ['created' => 0, 'updated' => 0, 'errors' => []];

    public function __construct(School $school)
    {
        $this->school = $school;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $data = $this->transformRow($row);

            if (! $data) {
                continue;
            }

            $subject = Subject::updateOrCreate(
                ['school_id' => $this->school->id, 'name' => $data['name']],
                $data
            );

            if ($subject->wasRecentlyCreated) {
                $this->results['created']++;
            } else {
                $this->results['updated']++;
            }
        }
    }

    protected function transformRow($row): ?array
    {
        $name = $this->getValue($row, ['name', 'nom', 'matiere', 'subject']);
        if (! $name) {
            return null;
        }

        return [
            'school_id' => $this->school->id,
            'name' => trim((string) $name),
            'code' => $this->getValue($row, ['code', 'abbreviation']),
            'default_coefficient' => (float) ($this->getValue($row, ['coefficient', 'default_coefficient', 'coef']) ?? 1.0),
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

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:10',
            'default_coefficient' => 'nullable|numeric|min:0|max:10',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'name.required' => 'Le nom de la matière est obligatoire',
            'default_coefficient.numeric' => 'Le coefficient doit être un nombre',
        ];
    }

    public function getResults(): array
    {
        return $this->results;
    }
}