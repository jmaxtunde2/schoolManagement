<?php

namespace App\Imports;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class AcademicPeriodImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    protected School $school;
    protected array $results = ['created' => 0, 'updated' => 0, 'errors' => []];
    protected array $yearCache = [];

    public function __construct(School $school)
    {
        $this->school = $school;
        $this->loadYearCache();
    }

    protected function loadYearCache(): void
    {
        $this->yearCache = AcademicYear::where('school_id', $this->school->id)
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

            $yearName = $data['academic_year'] ?? null;
            $yearId = $yearName ? ($this->yearCache[$yearName] ?? null) : null;

            if (! $yearId && $yearName) {
                $this->results['errors'][] = "Année scolaire '$yearName' introuvable pour la période {$data['name']}";
                continue;
            }

            $period = AcademicPeriod::updateOrCreate(
                ['school_id' => $this->school->id, 'academic_year_id' => $yearId, 'name' => $data['name']],
                array_merge($data, ['academic_year_id' => $yearId])
            );

            if ($period->wasRecentlyCreated) {
                $this->results['created']++;
            } else {
                $this->results['updated']++;
            }
        }
    }

    protected function transformRow($row): ?array
    {
        $name = $this->getValue($row, ['name', 'nom', 'periode', 'period']);
        if (! $name) {
            return null;
        }

        return [
            'school_id' => $this->school->id,
            'name' => trim((string) $name),
            'academic_year' => $this->getValue($row, ['academic_year', 'annee_scolaire', 'annee']),
            'starts_at' => $this->parseDate($this->getValue($row, ['starts_at', 'date_debut', 'debut'])),
            'ends_at' => $this->parseDate($this->getValue($row, ['ends_at', 'date_fin', 'fin'])),
            'is_active' => $this->parseBoolean($this->getValue($row, ['is_active', 'actif', 'active'])),
            'type' => $this->getValue($row, ['type', 'trimestre', 'semestre']) ?? 'trimestre',
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
            'academic_year' => 'required|string',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'nullable|boolean',
            'type' => 'nullable|string|in:trimestre,semestre',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'name.required' => 'Le nom de la période est obligatoire',
            'academic_year.required' => 'L\'année scolaire est obligatoire',
            'ends_at.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début',
        ];
    }

    public function getResults(): array
    {
        return $this->results;
    }
}