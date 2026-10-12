<?php

namespace App\Http\Controllers\Finance;

use App\Actions\Finance\BuildMoneyEvolution;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\ContributionRequest;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentContribution;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Contributions des élèves (scolarité, frais annexes…).
 *
 * Liste filtrable + statistiques + évolution des encaissements. La saisie d'une
 * contribution est possible depuis l'établissement (admin / secrétaire).
 */
class ContributionController extends Controller
{
    public function index(Request $request, BuildMoneyEvolution $evolution): Response
    {
        $user = $request->user();

        $filters = $request->validate([
            'academic_year_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(['paid', 'partial', 'pending'])],
            'search' => ['nullable', 'string', 'max:120'],
            'tab' => ['nullable', Rule::in(['liste', 'evolution'])],
        ]);

        $query = StudentContribution::query()
            ->with(['student:id,first_name,last_name,matricule', 'academicYear:id,name'])
            ->when(! empty($filters['academic_year_id']), fn ($q) => $q->where('academic_year_id', $filters['academic_year_id']))
            ->when(! empty($filters['status']), fn ($q) => $this->statusQuery($q, $filters['status']))
            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $term = '%'.$filters['search'].'%';
                $q->whereHas('student', fn ($students) => $students
                    ->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('matricule', 'like', $term));
            });

        $contributions = $query->latest('id')->paginate(20)->withQueryString()
            ->through(fn (StudentContribution $contribution) => $this->present($contribution));

        $aggregate = StudentContribution::query();

        return Inertia::render('Admin/Contributions/Index', [
            'contributions' => $contributions,
            'academicYears' => AcademicYear::query()->orderByDesc('starts_on')->get(['id', 'name']),
            'students' => Student::query()
                ->where('is_active', true)
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'matricule'])
                ->map(fn (Student $student) => [
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'matricule' => $student->matricule,
                ]),
            'filters' => $filters,
            'routePrefix' => $this->routePrefix($request),
            'initialTab' => $filters['tab'] ?? 'liste',
            'currentAcademicYearId' => AcademicYear::query()->where('is_current', true)->value('id'),
            'can_manage' => $user->isAdmin() || $user->isSecretary(),
            'stats' => [
                'collected' => (int) (clone $aggregate)->sum('amount_paid'),
                'due' => (int) (clone $aggregate)->sum('amount_due'),
                'remaining' => (int) (clone $aggregate)->get()
                    ->reduce(fn ($carry, $contribution) => $carry + max(0, $contribution->amount_due - $contribution->amount_paid), 0),
                'rate' => $this->rate((clone $aggregate)->sum('amount_paid'), (clone $aggregate)->sum('amount_due')),
            ],
            'evolution' => $evolution->handle('contribution'),
        ]);
    }

    public function store(ContributionRequest $request, AuditService $audit): RedirectResponse
    {
        $due = (int) $request->validated('amount_due');
        $paid = (int) ($request->validated('amount_paid') ?? 0);
        $status = $paid >= $due ? 'paid' : ($paid > 0 ? 'partial' : 'pending');

        $contribution = StudentContribution::query()->updateOrCreate(
            [
                'school_id' => $request->user()->school_id,
                'student_id' => $request->validated('student_id'),
                'academic_year_id' => $request->validated('academic_year_id'),
            ],
            [
                'amount_due' => $due,
                'amount_paid' => $paid,
                'status' => $status,
                'paid_at' => $request->validated('paid_at') ?: ($paid >= $due ? now() : null),
            ]
        );

        $audit->log('contribution.saved', $contribution, [
            'student_id' => $contribution->student_id,
            'amount_due' => $due,
            'amount_paid' => $paid,
        ]);

        return back()->with('success', 'Contribution enregistrée.');
    }

    public function destroy(Request $request, StudentContribution $contribution, AuditService $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless((int) $contribution->school_id === (int) $request->user()->school_id, 403);

        $contribution->delete();
        $audit->log('contribution.deleted', $contribution, ['amount_due' => $contribution->amount_due]);

        return back()->with('success', 'Contribution supprimée.');
    }

    private function statusQuery($query, string $status)
    {
        return match ($status) {
            'paid' => fn ($q) => $q->where('amount_paid', '>=', DB::raw('amount_due')),
            'partial' => fn ($q) => $q->whereColumn('amount_paid', '>', 0)->whereColumn('amount_paid', '<', DB::raw('amount_due')),
            default => fn ($q) => $q->where('amount_paid', '<=', 0),
        };
    }

    private function rate(int $paid, int $due): int
    {
        return $due > 0 ? (int) round($paid / $due * 100) : 0;
    }

    private function present(StudentContribution $contribution): array
    {
        $due = (int) $contribution->amount_due;
        $paid = (int) $contribution->amount_paid;
        $status = match (true) {
            $paid >= $due => ['value' => 'paid', 'label' => 'Payée'],
            $paid > 0 => ['value' => 'partial', 'label' => 'Partielle'],
            default => ['value' => 'pending', 'label' => 'En attente'],
        };

        return [
            'id' => $contribution->id,
            'student' => trim($contribution->student?->full_name ?? '—'),
            'matricule' => $contribution->student?->matricule,
            'academic_year' => $contribution->academicYear?->name,
            'amount_due' => $due,
            'amount_paid' => $paid,
            'remaining' => max(0, $due - $paid),
            'status' => $status['value'],
            'status_label' => $status['label'],
            'paid_at' => $contribution->paid_at?->toDateString(),
        ];
    }

    private function routePrefix(Request $request): string
    {
        return $request->user()->isAdmin() ? 'admin' : 'secretary';
    }
}