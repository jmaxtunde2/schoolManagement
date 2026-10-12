<?php

namespace App\Imports;

use App\Models\Room;
use App\Models\School;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class RoomImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure
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

            $room = Room::updateOrCreate(
                ['school_id' => $this->school->id, 'name' => $data['name']],
                $data
            );

            if ($room->wasRecentlyCreated) {
                $this->results['created']++;
            } else {
                $this->results['updated']++;
            }
        }
    }

    protected function transformRow($row): ?array
    {
        $name = $this->getValue($row, ['name', 'nom', 'salle', 'room']);
        if (! $name) {
            return null;
        }

        return [
            'school_id' => $this->school->id,
            'name' => trim((string) $name),
            'building' => $this->getValue($row, ['building', 'batiment', 'bâtiment']),
            'capacity' => (int) ($this->getValue($row, ['capacity', 'capacite', 'places']) ?? 0) ?: null,
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
            'name' => 'required|string|max:60',
            'building' => 'nullable|string|max:60',
            'capacity' => 'nullable|integer|min:1|max:500',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'name.required' => 'Le nom de la salle est obligatoire',
            'capacity.integer' => 'La capacité doit être un nombre entier',
        ];
    }

    public function getResults(): array
    {
        return $this->results;
    }
}