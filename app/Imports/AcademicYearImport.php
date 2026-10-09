<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class AcademicYearImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure
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

            $year = AcademicYear::updateOrCreate(
                ['school_id' => $this->school->id, 'name' => $data['name']],
                $data
            );

            if ($year->wasRecentlyCreated) {
                $this->results['created']++;
            } else {
                $this->results['updated']++;
            }
        }
    }

    protected function transformRow($row): ?array
    {
        $name = $this->getValue($row, ['name', 'nom', 'annee', 'year']);
        if (! $name) {
            return null;
        }

        return [
            'school_id' => $this->school->id,
            'name' => trim((string) $name),
            'starts_at' => $this->parseDate($this->getValue($row, ['starts_at', 'date_debut', 'debut'])),
            'ends_at' => $this->parseDate($this->getValue($row, ['ends_at', 'date_fin', 'fin'])),
            'is_current' => $this->parseBoolean($this->getValue($row, ['is_current', 'actuelle', 'current'])),
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

    protected function parseBoolean($value): bool
    {
        if (! $value) {
            return false;
        }
        $value = strtolower(trim((string) $value));
        return in_array($value, ['1', 'true', 'oui', 'yes', 'vrai']);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_current' => 'nullable|boolean',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'name.required' => 'Le nom de l\'année scolaire est obligatoire',
            'ends_at.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début',
        ];
    }

    public function getResults(): array
    {
        return $this->results;
    }
}