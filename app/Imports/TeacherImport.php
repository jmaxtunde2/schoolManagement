<?php

namespace App\Imports;

use App\Models\ClassRoom;
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

            // Attach class/subject assignments if provided
            $this->syncAssignments($teacher, $data);
        }
    }

    protected function syncAssignments(Teacher $teacher, array $data): void
    {
        $subjects = $this->resolveSubjects($data['subjects'] ?? null);

        if ($subjects->isEmpty()) {
            return;
        }

        $classes = $this->resolveClasses($data['classes'] ?? null);

        // Sans classe explicite, on retombe sur les classes qui enseignent ces matières.
        if ($classes->isEmpty()) {
            $classes = ClassRoom::where('school_id', $this->school->id)
                ->whereIn('id', function ($query) use ($subjects) {
                    $query->select('class_id')
                        ->from('class_subject')
                        ->whereIn('subject_id', $subjects);
                })
                ->pluck('id');
        }

        if ($classes->isEmpty()) {
            $this->results['errors'][] = "Enseignant {$data['name']} : aucune classe trouvée pour les matières indiquées ; affectations ignorées.";

            return;
        }

        foreach ($classes as $classId) {
            foreach ($subjects as $subjectId) {
                $teacher->assignments()->firstOrCreate([
                    'class_id' => $classId,
                    'subject_id' => $subjectId,
                ]);
            }
        }
    }

    protected function resolveSubjects(?string $value): Collection
    {
        if (! $value) {
            return collect();
        }

        $codes = array_values(array_filter(array_map('trim', explode(',', $value))));

        if (empty($codes)) {
            return collect();
        }

        return Subject::where('school_id', $this->school->id)
            ->where(function ($query) use ($codes) {
                $query->whereIn('code', $codes)->orWhereIn('name', $codes);
            })
            ->pluck('id');
    }

    protected function resolveClasses(?string $value): Collection
    {
        if (! $value) {
            return collect();
        }

        $names = array_values(array_filter(array_map('trim', explode(',', $value))));

        if (empty($names)) {
            return collect();
        }

        return ClassRoom::where('school_id', $this->school->id)
            ->whereIn('name', $names)
            ->pluck('id');
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
            'classes' => $this->getValue($row, ['classes', 'classe', 'class']),
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
            'classes' => 'nullable|string',
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