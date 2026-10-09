<?php

namespace App\Imports;

use App\Models\Subject;
use App\Models\Teacher;
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

class TeacherImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    protected School $school;
    protected array $results = ['created' => 0, 'updated' => 0, 'users_created' => 0, 'errors' => []];

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

            // Create or find user
            $user = User::firstOrNew(['email' => $data['email']]);
            $isNewUser = ! $user->exists;

            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password'] ?? 'password123'),
                'role' => Role::Teacher,
                'school_id' => $this->school->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ])->save();

            if ($isNewUser) {
                $this->results['users_created']++;
            }

            // Create or update teacher profile
            $teacher = Teacher::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'school_id' => $this->school->id,
                    'employee_number' => $data['employee_number'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'hire_date' => $data['hire_date'] ?? now()->format('Y-m-d'),
                ]
            );

            if ($teacher->wasRecentlyCreated) {
                $this->results['created']++;
            } else {
                $this->results['updated']++;
            }

            // Attach subjects if provided
            if (! empty($data['subjects'])) {
                $subjectCodes = array_map('trim', explode(',', $data['subjects']));
                $subjects = Subject::where('school_id', $this->school->id)
                    ->whereIn('code', $subjectCodes)
                    ->orWhereIn('name', $subjectCodes)
                    ->pluck('id');

                if ($subjects->isNotEmpty()) {
                    $teacher->subjects()->sync($subjects);
                }
            }
        }
    }

    protected function transformRow($row): ?array
    {
        $email = $this->getValue($row, ['email', 'mail']);
        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $name = $this->getValue($row, ['name', 'nom', 'prenom_nom', 'full_name']);
        if (! $name) {
            return null;
        }

        return [
            'name' => trim((string) $name),
            'email' => strtolower(trim((string) $email)),
            'password' => $this->getValue($row, ['password', 'mot_de_passe']) ?? 'password123',
            'phone' => $this->getValue($row, ['phone', 'telephone', 'tel']),
            'employee_number' => $this->getValue($row, ['employee_number', 'matricule', 'numero_employe']),
            'hire_date' => $this->parseDate($this->getValue($row, ['hire_date', 'date_embauche', 'date_entree'])),
            'subjects' => $this->getValue($row, ['subjects', 'matieres', 'matiere']),
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

        // Try multiple formats
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'nullable|string|min:8',
            'phone' => 'nullable|string|max:20',
            'employee_number' => 'nullable|string|max:50',
            'hire_date' => 'nullable|date',
            'subjects' => 'nullable|string',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'name.required' => 'Le nom de l\'enseignant est obligatoire',
            'email.required' => 'L\'email de l\'enseignant est obligatoire',
            'email.email' => 'L\'email doit être valide',
        ];
    }

    public function getResults(): array
    {
        return $this->results;
    }
}