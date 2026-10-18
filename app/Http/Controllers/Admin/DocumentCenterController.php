<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Student;
use App\Services\Academic\AcademicCalculationService;
use App\Services\Assistant\AssistantContext;
use App\Services\Assistant\SchoolAcademicService;
use App\Services\Assistant\SchoolAnalyticsService;
use App\Services\Assistant\SchoolFinanceService;
use App\Services\Documents\SchoolDocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Centre de documents (phase 6).
 *
 * Génère et met à disposition les documents imprimables de l'établissement,
 * avec l'en-tête et l'identité visuelle de l'école. Les bulletins et reçus
 * disposent déjà de leurs propres écrans et sont référencés depuis le centre.
 */
class DocumentCenterController extends Controller
{
    public function index(Request $request): Response
    {
        $context = AssistantContext::for($request->user());

        $classes = $context->classes()->map(fn (ClassRoom $class) => [
            'id' => $class->id,
            'name' => $class->name,
            'level' => $class->level,
        ])->values();

        $students = Student::query()
            ->when(
                $context->allowedClassIds !== null,
                fn ($query) => $query->whereIn('class_id', $context->allowedClassIds),
            )
            ->where('is_active', true)
            ->with('classRoom:id,name')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'class_id', 'matricule', 'first_name', 'last_name'])
            ->map(fn (Student $student) => [
                'id' => $student->id,
                'name' => $student->full_name,
                'matricule' => $student->matricule,
                'class_name' => $student->classRoom?->name,
            ])
            ->values();

        return Inertia::render('Admin/Documents/Index', [
            'classes' => $classes,
            'students' => $students,
            'canViewFinance' => $context->canViewFinance,
            'scope_note' => $context->scopeNote(),
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    public function certificate(Request $request, Student $student, SchoolDocumentService $documents): HttpResponse
    {
        $this->ensureStudentInScope(AssistantContext::for($request->user()), $student);

        $school = $this->school($request);

        return $this->pdf(
            $documents->certificate($school, $student, 'scolarite'),
            'certificat-scolarite-'.$student->id.'.pdf',
        );
    }

    public function attestation(Request $request, Student $student, SchoolDocumentService $documents): HttpResponse
    {
        $this->ensureStudentInScope(AssistantContext::for($request->user()), $student);

        $school = $this->school($request);

        return $this->pdf(
            $documents->certificate($school, $student, 'attestation'),
            'attestation-scolarite-'.$student->id.'.pdf',
        );
    }

    public function classList(Request $request, ClassRoom $classRoom, SchoolDocumentService $documents): HttpResponse
    {
        $context = AssistantContext::for($request->user());
        $this->ensureClassInScope($context, $classRoom);

        $students = Student::query()
            ->where('class_id', $classRoom->id)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'matricule', 'first_name', 'last_name', 'gender', 'birth_date', 'is_active']);

        $rows = $students->values()->map(fn (Student $student, int $index) => [
            $index + 1,
            $student->matricule ?: '—',
            $student->full_name,
            $student->gender === 'F' ? 'F' : ($student->gender === 'M' ? 'M' : '—'),
            optional($student->birth_date)->format('d/m/Y') ?? '—',
            $student->is_active ? 'Actif' : 'Inactif',
        ])->all();

        return $this->pdf($documents->table($this->school($request), [
            'title' => 'Liste des élèves',
            'subtitle' => 'Classe de '.$classRoom->name,
            'meta' => ['Classe' => $classRoom->name, 'Niveau' => $classRoom->level ?? '—', 'Effectif' => (string) $students->count()],
            'tables' => [[
                'columns' => [
                    ['label' => 'N°', 'center' => true],
                    ['label' => 'Matricule'],
                    ['label' => 'Nom et prénoms'],
                    ['label' => 'Sexe', 'center' => true],
                    ['label' => 'Naissance', 'center' => true],
                    ['label' => 'Statut', 'center' => true],
                ],
                'rows' => $rows,
            ]],
        ]), 'liste-eleves-'.str($classRoom->name)->slug().'.pdf');
    }

    public function studentsList(Request $request, SchoolDocumentService $documents): HttpResponse
    {
        $context = AssistantContext::for($request->user());

        $students = Student::query()
            ->when(
                $context->allowedClassIds !== null,
                fn ($query) => $query->whereIn('class_id', $context->allowedClassIds),
            )
            ->with('classRoom:id,name')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'class_id', 'matricule', 'first_name', 'last_name', 'gender']);

        $rows = $students->values()->map(fn (Student $student, int $index) => [
            $index + 1,
            $student->matricule ?: '—',
            $student->full_name,
            $student->classRoom?->name ?? '—',
            $student->gender === 'F' ? 'F' : ($student->gender === 'M' ? 'M' : '—'),
        ])->all();

        return $this->pdf($documents->table($this->school($request), [
            'title' => 'Liste générale des élèves',
            'meta' => ['Effectif total' => (string) $students->count()],
            'tables' => [[
                'columns' => [
                    ['label' => 'N°', 'center' => true],
                    ['label' => 'Matricule'],
                    ['label' => 'Nom et prénoms'],
                    ['label' => 'Classe'],
                    ['label' => 'Sexe', 'center' => true],
                ],
                'rows' => $rows,
            ]],
        ]), 'liste-generale-eleves.pdf');
    }

    public function roomList(Request $request, SchoolDocumentService $documents): HttpResponse
    {
        $context = AssistantContext::for($request->user());
        $distribution = (new SchoolAnalyticsService($context))->distributionByClass();

        $rows = collect($distribution)->values()->map(fn (array $row, int $index) => [
            $index + 1,
            $row['name'],
            $row['total'],
        ])->all();

        return $this->pdf($documents->table($this->school($request), [
            'title' => 'Liste des classes',
            'meta' => ['Nombre de classes' => (string) $context->classes()->count()],
            'tables' => [[
                'columns' => [
                    ['label' => 'N°', 'center' => true],
                    ['label' => 'Classe'],
                    ['label' => 'Effectif', 'center' => true],
                ],
                'rows' => $rows,
            ]],
        ]), 'liste-classes.pdf');
    }

    public function statistics(Request $request, SchoolDocumentService $documents): HttpResponse
    {
        $context = AssistantContext::for($request->user());
        $analytics = new SchoolAnalyticsService($context);
        $academic = new SchoolAcademicService($context, app(AcademicCalculationService::class));

        $students = $analytics->studentCount();
        $overall = $academic->overall();

        $classRows = collect($academic->classRanking())->map(fn (array $row, int $index) => [
            $index + 1,
            $row['class_name'],
            $row['average'].'/20',
            $row['students'],
        ])->all();

        $subjectRows = collect($academic->subjectAverages())->map(fn (array $row, int $index) => [
            $index + 1,
            $row['subject'],
            $row['average'].'/20',
        ])->all();

        return $this->pdf($documents->table($this->school($request), [
            'title' => 'Rapport statistique',
            'subtitle' => $context->currentPeriod?->name,
            'meta' => [
                'Élèves actifs' => (string) $students['active'],
                'Filles' => (string) $students['female'],
                'Garçons' => (string) $students['male'],
                'Classes' => (string) $analytics->classCount(),
                'Enseignants' => (string) $analytics->teacherCount(),
                'Moyenne générale' => $overall['average'] !== null ? $overall['average'].'/20' : '—',
                'Taux de réussite' => $overall['success_rate'] !== null ? $overall['success_rate'].'%' : '—',
            ],
            'tables' => [
                [
                    'title' => 'Moyenne par classe',
                    'columns' => [
                        ['label' => 'N°', 'center' => true],
                        ['label' => 'Classe'],
                        ['label' => 'Moyenne', 'center' => true],
                        ['label' => 'Élèves évalués', 'center' => true],
                    ],
                    'rows' => $classRows,
                ],
                [
                    'title' => 'Moyenne par matière',
                    'columns' => [
                        ['label' => 'N°', 'center' => true],
                        ['label' => 'Matière'],
                        ['label' => 'Moyenne', 'center' => true],
                    ],
                    'rows' => $subjectRows,
                ],
            ],
        ]), 'rapport-statistique.pdf');
    }

    public function finance(Request $request, SchoolDocumentService $documents): HttpResponse
    {
        $context = AssistantContext::for($request->user());
        abort_unless($context->canViewFinance, 403);

        $finance = new SchoolFinanceService($context);
        $summary = $finance->summary('year');
        $money = fn (int $value) => number_format($value, 0, ',', ' ').' FCFA';

        $rows = collect($finance->expensesByCategory('year'))->map(fn (array $row, int $index) => [
            $index + 1,
            $row['label'],
            $money($row['total']),
        ])->all();

        return $this->pdf($documents->table($this->school($request), [
            'title' => 'Rapport financier',
            'subtitle' => 'Exercice en cours',
            'meta' => [
                'Recettes' => $money($summary['revenue']),
                'Dépenses' => $money($summary['expenses']),
                'Solde' => $money($summary['balance']),
                'Impayés' => $money($finance->outstanding()),
            ],
            'tables' => [[
                'title' => 'Dépenses par catégorie',
                'columns' => [
                    ['label' => 'N°', 'center' => true],
                    ['label' => 'Catégorie'],
                    ['label' => 'Montant', 'center' => true],
                ],
                'rows' => $rows,
            ]],
        ]), 'rapport-financier.pdf');
    }

    private function ensureStudentInScope(AssistantContext $context, Student $student): void
    {
        if ($context->allowedClassIds !== null && ! in_array((int) $student->class_id, $context->allowedClassIds, true)) {
            abort(403);
        }
    }

    private function ensureClassInScope(AssistantContext $context, ClassRoom $classRoom): void
    {
        if ($context->allowedClassIds !== null && ! in_array((int) $classRoom->id, $context->allowedClassIds, true)) {
            abort(403);
        }
    }

    private function school(Request $request): School
    {
        return School::findOrFail($request->user()->school_id);
    }

    private function pdf(string $content, string $filename): HttpResponse
    {
        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    private function routePrefix(Request $request): string
    {
        return $request->routeIs('director.*') ? 'director' : 'admin';
    }
}
