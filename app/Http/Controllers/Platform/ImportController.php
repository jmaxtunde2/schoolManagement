<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\ImportRequest;
use App\Imports\AcademicPeriodImport;
use App\Imports\AcademicYearImport;
use App\Imports\ClassRoomImport;
use App\Imports\ParentImport;
use App\Imports\StudentImport;
use App\Imports\SubjectImport;
use App\Imports\TeacherImport;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    protected array $importTypes = [
        'classes' => [
            'label' => 'Classes',
            'import' => ClassRoomImport::class,
            'description' => 'Importer les classes (nom, niveau, capacité)',
            'required_columns' => ['name'],
            'optional_columns' => ['level', 'capacity'],
            'sample_headers' => ['name', 'level', 'capacity'],
        ],
        'subjects' => [
            'label' => 'Matières',
            'import' => SubjectImport::class,
            'description' => 'Importer les matières (nom, code, coefficient)',
            'required_columns' => ['name'],
            'optional_columns' => ['code', 'default_coefficient'],
            'sample_headers' => ['name', 'code', 'default_coefficient'],
        ],
        'teachers' => [
            'label' => 'Enseignants',
            'import' => TeacherImport::class,
            'description' => 'Importer les enseignants (nom, email, téléphone, matières, classes)',
            'required_columns' => ['name', 'email'],
            'optional_columns' => ['password', 'phone', 'employee_number', 'hire_date', 'subjects', 'classes'],
            'sample_headers' => ['name', 'email', 'password', 'phone', 'employee_number', 'hire_date', 'subjects', 'classes'],
        ],
        'students' => [
            'label' => 'Élèves',
            'import' => StudentImport::class,
            'description' => 'Importer les élèves (matricule, nom, prénom, classe, date naissance)',
            'required_columns' => ['student_number', 'first_name', 'last_name'],
            'optional_columns' => ['gender', 'birth_date', 'birth_place', 'address', 'phone', 'class'],
            'sample_headers' => ['student_number', 'first_name', 'last_name', 'gender', 'birth_date', 'birth_place', 'address', 'phone', 'class'],
        ],
        'parents' => [
            'label' => 'Parents',
            'import' => ParentImport::class,
            'description' => 'Importer les parents (nom, téléphone, email, enfants)',
            'required_columns' => ['name', 'phone'],
            'optional_columns' => ['email', 'password', 'address', 'profession', 'relationship', 'student_numbers'],
            'sample_headers' => ['name', 'phone', 'email', 'password', 'address', 'profession', 'relationship', 'student_numbers'],
        ],
        'academic_years' => [
            'label' => 'Années scolaires',
            'import' => AcademicYearImport::class,
            'description' => 'Importer les années scolaires (nom, date début, date fin)',
            'required_columns' => ['name'],
            'optional_columns' => ['starts_at', 'ends_at', 'is_current'],
            'sample_headers' => ['name', 'starts_at', 'ends_at', 'is_current'],
        ],
        'academic_periods' => [
            'label' => 'Périodes scolaires',
            'import' => AcademicPeriodImport::class,
            'description' => 'Importer les périodes (nom, année scolaire, dates, type)',
            'required_columns' => ['name', 'academic_year'],
            'optional_columns' => ['starts_at', 'ends_at', 'is_active', 'type'],
            'sample_headers' => ['name', 'academic_year', 'starts_at', 'ends_at', 'is_active', 'type'],
        ],
    ];

    public function index(Request $request): Response
    {
        $schools = School::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return Inertia::render('Platform/Imports/Index', [
            'schools' => $schools,
            'importTypes' => $this->importTypes,
        ]);
    }

    public function show(Request $request, School $school): Response
    {
        return Inertia::render('Platform/Imports/Show', [
            'school' => [
                'id' => $school->id,
                'name' => $school->name,
                'slug' => $school->slug,
            ],
            'importTypes' => $this->importTypes,
        ]);
    }

    public function store(ImportRequest $request, School $school): RedirectResponse
    {
        $type = $request->input('type');
        $file = $request->file('file');

        if (! isset($this->importTypes[$type])) {
            return back()->withErrors(['type' => 'Type d\'import invalide.']);
        }

        $config = $this->importTypes[$type];
        $importClass = $config['import'];

        try {
            $import = new $importClass($school);

            Excel::import($import, $file);

            $results = $import->getResults();

            $message = "Import « {$config['label']} » terminé : "
                . ($results['created'] ?? 0) . " créé(s), "
                . ($results['updated'] ?? 0) . " mis à jour";

            if ($type === 'teachers') {
                $message .= ", " . ($results['users_created'] ?? 0) . " utilisateurs créés";
            } elseif ($type === 'parents') {
                $message .= ", " . ($results['users_created'] ?? 0) . " utilisateurs créés, "
                    . ($results['linked_students'] ?? 0) . " élèves liés";
            }

            if (! empty($results['errors'])) {
                $message .= ". Erreurs : " . count($results['errors']);
            }

            return redirect()
                ->route('platform.imports.show', $school)
                ->with('success', $message)
                ->with('import_results', $results);

        } catch (\Exception $e) {
            return back()
                ->withErrors(['file' => 'Erreur lors de l\'import : ' . $e->getMessage()]);
        }
    }

    public function downloadTemplate(Request $request, string $type): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        if (! isset($this->importTypes[$type])) {
            abort(404);
        }

        $config = $this->importTypes[$type];
        $headers = array_merge(
            $config['required_columns'] ?? [],
            $config['optional_columns'] ?? []
        );

        // Exemples de lignes selon le type
        $examples = $this->getTemplateExamples($type, $headers);

        // Create CSV with headers + example rows
        $csv = implode(',', $headers) . "\n";
        foreach ($examples as $row) {
            $csv .= implode(',', array_map(fn($h) => $row[$h] ?? '', $headers)) . "\n";
        }
        $filename = "import_template_{$type}.csv";

        $tempFile = tempnam(sys_get_temp_dir(), 'import_');
        file_put_contents($tempFile, $csv);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    protected function getTemplateExamples(string $type, array $headers): array
    {
        $examples = [];

        switch ($type) {
            case 'classes':
                $examples[] = [
                    'name' => '6ème A',
                    'level' => '6ème',
                    'capacity' => '40',
                ];
                $examples[] = [
                    'name' => '5ème B',
                    'level' => '5ème',
                    'capacity' => '35',
                ];
                $examples[] = [
                    'name' => 'Terminale C',
                    'level' => 'Terminale',
                    'capacity' => '30',
                ];
                break;

            case 'subjects':
                $examples[] = [
                    'name' => 'Mathématiques',
                    'code' => 'MATH',
                    'default_coefficient' => '3.0',
                ];
                $examples[] = [
                    'name' => 'Français',
                    'code' => 'FR',
                    'default_coefficient' => '3.0',
                ];
                $examples[] = [
                    'name' => 'Anglais',
                    'code' => 'ANG',
                    'default_coefficient' => '2.0',
                ];
                break;

            case 'teachers':
                $examples[] = [
                    'name' => 'Jean DUPONT',
                    'email' => 'jean.dupont@ecole.test',
                    'password' => 'password123',
                    'phone' => '+229 01 23 45 67',
                    'employee_number' => 'ENS001',
                    'hire_date' => '2020-09-01',
                    'subjects' => 'MATH,FR',
                ];
                $examples[] = [
                    'name' => 'Marie MARTIN',
                    'email' => 'marie.martin@ecole.test',
                    'password' => 'password123',
                    'phone' => '+229 01 23 45 68',
                    'employee_number' => 'ENS002',
                    'hire_date' => '2019-09-01',
                    'subjects' => 'ANG,FR',
                ];
                break;

            case 'students':
                $examples[] = [
                    'student_number' => 'ELE001',
                    'first_name' => 'Amadou',
                    'last_name' => 'TRAORE',
                    'gender' => 'M',
                    'birth_date' => '2010-05-15',
                    'birth_place' => 'Cotonou',
                    'address' => 'Quartier Haie Vive',
                    'phone' => '+229 01 23 45 69',
                    'class' => '6ème A',
                ];
                $examples[] = [
                    'student_number' => 'ELE002',
                    'first_name' => 'Fatou',
                    'last_name' => 'ADJOVA',
                    'gender' => 'F',
                    'birth_date' => '2010-08-22',
                    'birth_place' => 'Porto-Novo',
                    'address' => 'Quartier Ganhi',
                    'phone' => '+229 01 23 45 70',
                    'class' => '6ème A',
                ];
                break;

            case 'parents':
                $examples[] = [
                    'name' => 'Ibrahim TRAORE',
                    'phone' => '+229 01 23 45 71',
                    'email' => 'ibrahim.traore@email.test',
                    'password' => 'password123',
                    'address' => 'Quartier Haie Vive',
                    'profession' => 'Commerçant',
                    'relationship' => 'pere',
                    'student_numbers' => 'ELE001,ELE002',
                ];
                $examples[] = [
                    'name' => 'Aissatou ADJOVA',
                    'phone' => '+229 01 23 45 72',
                    'email' => 'aissatou.adjova@email.test',
                    'password' => 'password123',
                    'address' => 'Quartier Ganhi',
                    'profession' => 'Enseignante',
                    'relationship' => 'mere',
                    'student_numbers' => 'ELE002',
                ];
                break;

            case 'academic_years':
                $examples[] = [
                    'name' => '2024-2025',
                    'starts_at' => '2024-09-01',
                    'ends_at' => '2025-06-30',
                    'is_current' => 'true',
                ];
                $examples[] = [
                    'name' => '2025-2026',
                    'starts_at' => '2025-09-01',
                    'ends_at' => '2026-06-30',
                    'is_current' => 'false',
                ];
                break;

            case 'academic_periods':
                $examples[] = [
                    'name' => '1er Trimestre',
                    'academic_year' => '2024-2025',
                    'starts_at' => '2024-09-01',
                    'ends_at' => '2024-12-20',
                    'is_active' => 'true',
                    'type' => 'trimestre',
                ];
                $examples[] = [
                    'name' => '2ème Trimestre',
                    'academic_year' => '2024-2025',
                    'starts_at' => '2025-01-06',
                    'ends_at' => '2025-04-11',
                    'is_active' => 'false',
                    'type' => 'trimestre',
                ];
                $examples[] = [
                    'name' => '3ème Trimestre',
                    'academic_year' => '2024-2025',
                    'starts_at' => '2025-04-28',
                    'ends_at' => '2025-06-30',
                    'is_active' => 'false',
                    'type' => 'trimestre',
                ];
                break;
        }

        return $examples;
    }
}