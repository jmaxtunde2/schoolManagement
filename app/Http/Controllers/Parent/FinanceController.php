<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ContributionPayment;
use App\Models\ParentGuardian;
use App\Models\StudentContribution;
use App\Services\Finance\PaymentDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Espace parent — historique des paiements de scolarité, filtres et téléchargements.
 * Toutes les requêtes sont bornées aux enfants du parent connecté (jamais d'ID client).
 */
class FinanceController extends Controller
{
    public function index(Request $request): Response
    {
        $parent = $this->parent($request);
        $filters = $this->filters($request);

        [$contributions, $payments, $totals] = $this->data($parent, $filters);

        return Inertia::render('Parent/Finance/Index', [
            'children' => $this->children($parent),
            'academicYears' => $this->academicYears($contributions->pluck('academic_year_id')->unique()->all()),
            'methods' => collect(ContributionPayment::METHODS)
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values(),
            'filters' => $filters,
            'totals' => $totals,
            'payments' => $payments->map(fn (ContributionPayment $payment) => [
                'id' => $payment->id,
                'paid_at' => optional($payment->paid_at)->format('d/m/Y'),
                'amount' => (int) $payment->amount,
                'method' => $payment->method,
                'method_label' => $payment->methodLabel(),
                'reference' => $payment->reference,
                'receipt_number' => $payment->receipt_number,
                'student' => $payment->contribution?->student?->full_name,
                'academic_year' => $payment->contribution?->academicYear?->name,
                'status' => $payment->contribution?->status,
                'status_label' => $payment->contribution?->statusLabel(),
            ])->values(),
        ]);
    }

    public function statement(Request $request, PaymentDocumentService $documents): StreamedResponse
    {
        $parent = $this->parent($request);
        [, $payments, $totals] = $this->data($parent, $this->filters($request));

        $bytes = $documents->statement($request->user()->school, $parent, $payments, $totals);

        return response()->streamDownload(function () use ($bytes): void {
            echo $bytes;
        }, 'releve-scolarite-'.$parent->id.'.pdf', ['Content-Type' => 'application/pdf']);
    }

    public function receipt(Request $request, ContributionPayment $payment, PaymentDocumentService $documents): StreamedResponse
    {
        $parent = $this->parent($request);
        $payment->loadMissing('contribution');

        $studentId = $payment->contribution?->student_id;
        $owns = $studentId !== null
            && $parent->students()->where('students.id', $studentId)->exists();

        abort_unless($owns, 403);

        $bytes = $documents->receipt($payment);

        return response()->streamDownload(function () use ($bytes): void {
            echo $bytes;
        }, 'recu-'.($payment->receipt_number ?: $payment->id).'.pdf', ['Content-Type' => 'application/pdf']);
    }

    private function parent(Request $request): ParentGuardian
    {
        return $request->user()->parentGuardian()->firstOrFail();
    }

    /** @return array{student_id: ?int, academic_year_id: ?int, status: ?string, method: ?string} */
    private function filters(Request $request): array
    {
        $status = $request->input('status');
        $method = $request->input('method');

        return [
            'student_id' => $request->integer('student_id') ?: null,
            'academic_year_id' => $request->integer('academic_year_id') ?: null,
            'status' => in_array($status, ['pending', 'partial', 'paid'], true) ? $status : null,
            'method' => is_string($method) && array_key_exists($method, ContributionPayment::METHODS) ? $method : null,
        ];
    }

    /**
     * @param  array{student_id: ?int, academic_year_id: ?int, status: ?string, method: ?string}  $filters
     * @return array{0: Collection<int, StudentContribution>, 1: Collection<int, ContributionPayment>, 2: array{due: int, paid: int, remaining: int}}
     */
    private function data(ParentGuardian $parent, array $filters): array
    {
        $studentIds = $parent->students()->pluck('students.id');

        $contributions = StudentContribution::query()
            ->whereIn('student_id', $studentIds)
            ->when($filters['student_id'], fn ($query, $value) => $query->where('student_id', $value))
            ->when($filters['academic_year_id'], fn ($query, $value) => $query->where('academic_year_id', $value))
            ->when($filters['status'], fn ($query, $value) => $query->where('status', $value))
            ->with(['student.classRoom', 'academicYear'])
            ->get();

        $payments = ContributionPayment::query()
            ->whereIn('student_contribution_id', $contributions->pluck('id'))
            ->when($filters['method'], fn ($query, $value) => $query->where('method', $value))
            ->with(['contribution.student', 'contribution.academicYear'])
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get();

        $totals = [
            'due' => (int) $contributions->sum('amount_due'),
            'paid' => (int) $contributions->sum('amount_paid'),
            'remaining' => (int) $contributions->sum(fn (StudentContribution $contribution) => $contribution->remaining()),
        ];

        return [$contributions, $payments, $totals];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function children(ParentGuardian $parent): Collection
    {
        return $parent->students()
            ->orderBy('students.id')
            ->get(['students.id', 'students.first_name', 'students.last_name', 'students.matricule'])
            ->map(fn ($student) => [
                'id' => $student->id,
                'name' => $student->full_name,
                'matricule' => $student->matricule,
            ]);
    }

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, AcademicYear>
     */
    private function academicYears(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return AcademicYear::query()
            ->whereIn('id', $ids)
            ->orderByDesc('is_current')
            ->orderByDesc('starts_on')
            ->get(['id', 'name', 'is_current']);
    }
}
