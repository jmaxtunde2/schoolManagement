<?php

namespace App\Imports;

use App\Models\ClassRoom;
use App\Models\School;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Validators\Failure;

class ClassRoomImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure
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

            $classRoom = ClassRoom::updateOrCreate(
                ['school_id' => $this->school->id, 'name' => $data['name']],
                $data
            );

            if ($classRoom->wasRecentlyCreated) {
                $this->results['created']++;
            } else {
                $this->results['updated']++;
            }
        }
    }

    protected function transformRow($row): ?array
    {
        $name = $this->getValue($row, ['name', 'nom', 'classe', 'class']);
        if (! $name) {
            return null;
        }

        return [
            'school_id' => $this->school->id,
            'name' => trim((string) $name),
            'level' => $this->getValue($row, ['level', 'niveau', 'annee']),
            'capacity' => (int) ($this->getValue($row, ['capacity', 'capacite', 'effectif_max']) ?? 40),
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
            'level' => 'nullable|string|max:50',
            'capacity' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'name.required' => 'Le nom de la classe est obligatoire',
            'capacity.integer' => 'La capacité doit être un nombre entier',
        ];
    }

    public function getResults(): array
    {
        return $this->results;
    }
}