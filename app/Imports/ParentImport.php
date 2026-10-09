<?php

namespace App\Imports;

use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\User;
use App\Models\School;
use App\Enums\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class ParentImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    protected School $school;
    protected array $results = ['created' => 0, 'updated' => 0, 'users_created' => 0, 'linked_students' => 0, 'errors' => []];
    protected array $studentCache = [];

    public function __construct(School $school)
    {
        $this->school = $school;
        $this->loadStudentCache();
    }

    protected function loadStudentCache(): void
    {
        $this->studentCache = Student::where('school_id', $this->school->id)
            ->pluck('id', 'student_number')
            ->toArray();
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $data = $this->transformRow($row);

            if (! $data) {
                continue;
            }

            // Create or find user
            $email = $data['email'] ?? null;
            $user = null;

            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $user = User::firstOrNew(['email' => strtolower($email)]);
                $isNewUser = ! $user->exists;

                $user->fill([
                    'name' => $data['name'],
                    'email' => strtolower($email),
                    'password' => Hash::make($data['password'] ?? 'password123'),
                    'role' => Role::Parent,
                    'school_id' => $this->school->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ])->save();

                if ($isNewUser) {
                    $this->results['users_created']++;
                }
            }

            // Create or update parent profile
            $parent = ParentGuardian::updateOrCreate(
                ['school_id' => $this->school->id, 'phone' => $data['phone']],
                [
                    'user_id' => $user?->id,
                    'first_name' => $data['first_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                    'email' => $email,
                    'phone' => $data['phone'],
                    'address' => $data['address'] ?? null,
                    'profession' => $data['profession'] ?? null,
                    'relationship' => $data['relationship'] ?? 'parent',
                ]
            );

            if ($parent->wasRecentlyCreated) {
                $this->results['created']++;
            } else {
                $this->results['updated']++;
            }

            // Link students if provided
            if (! empty($data['student_numbers'])) {
                $numbers = array_map('trim', explode(',', $data['student_numbers']));
                foreach ($numbers as $number) {
                    if (isset($this->studentCache[$number])) {
                        $parent->students()->syncWithoutDetaching([$this->studentCache[$number]]);
                        $this->results['linked_students']++;
                    }
                }
            }
        }
    }

    protected function transformRow($row): ?array
    {
        $phone = $this->getValue($row, ['phone', 'telephone', 'tel']);
        if (! $phone) {
            return null;
        }

        $name = $this->getValue($row, ['name', 'nom', 'prenom_nom', 'full_name']);
        if (! $name) {
            return null;
        }

        // Split name if needed
        $parts = explode(' ', trim($name), 2);
        $firstName = $parts[0] ?? '';
        $lastName = $parts[1] ?? '';

        return [
            'name' => trim((string) $name),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $this->getValue($row, ['email', 'mail']) ? strtolower(trim($this->getValue($row, ['email', 'mail']))) : null,
            'phone' => trim((string) $phone),
            'password' => $this->getValue($row, ['password', 'mot_de_passe']) ?? 'password123',
            'address' => $this->getValue($row, ['address', 'adresse']),
            'profession' => $this->getValue($row, ['profession', 'metier']),
            'relationship' => $this->getValue($row, ['relationship', 'lien', 'parente']) ?? 'parent',
            'student_numbers' => $this->getValue($row, ['student_numbers', 'matricules_eleves', 'numeros_eleves', 'enfants']),
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
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:8',
            'address' => 'nullable|string|max:500',
            'profession' => 'nullable|string|max:100',
            'relationship' => 'nullable|string|max:50',
            'student_numbers' => 'nullable|string',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'name.required' => 'Le nom du parent est obligatoire',
            'phone.required' => 'Le téléphone du parent est obligatoire',
        ];
    }

    public function getResults(): array
    {
        return $this->results;
    }
}