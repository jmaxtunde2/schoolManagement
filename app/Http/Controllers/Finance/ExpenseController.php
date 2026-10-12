<?php

namespace App\Http\Controllers\Finance;

use App\Actions\Finance\BuildMoneyEvolution;
use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\ExpenseRequest;
use App\Models\Expense;
use App\Models\School;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestion des dépenses (création, approbation, seuil).
 *
 * - La secrétaire (et l'administrateur) créent les dépenses.
 * - Une dépense dont le montant dépasse le seuil configuré doit être approuvée
 *   par l'administrateur ; en dessous, elle est enregistrée directement.
 * - Approbation, rejet, suppression et configuration du seuil : administrateur uniquement.
 */
class ExpenseController extends Controller
{
    public function index(Request $request, BuildMoneyEvolution $evolution): Response
    {
        $user = $request->user();
        $school = $user->school;

        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(ExpenseStatus::class)],
            'category' => ['nullable', Rule::enum(ExpenseCategory::class)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:120'],
            'tab' => ['nullable', Rule::in(['liste', 'evolution'])],
        ]);

        $query = Expense::query()
            ->with(['creator:id,name'])
            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['category']), fn ($q) => $q->where('category', $filters['category']))
            ->when(! empty($filters['date_from']), fn ($q) => $q->whereDate('expense_date', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($q) => $q->whereDate('expense_date', '<=', $filters['date_to']))
            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $term = '%'.$filters['search'].'%';
                $q->where(fn ($search) => $search->where('label', 'like', $term)->orWhere('reference', 'like', $term));
            });

        $expenses = $query->latest('expense_date')->latest('id')->paginate(20)->withQueryString()
            ->through(fn (Expense $expense) => $this->present($expense));

        $monthStart = now()->startOfMonth()->toDateString();
        $monthAgg = Expense::query()
            ->where('status', '!=', ExpenseStatus::Rejected->value)
            ->whereDate('expense_date', '>=', $monthStart);

        $threshold = $this->threshold($school);

        return Inertia::render('Admin/Expenses/Index', [
            'expenses' => $expenses,
            'categories' => $this->categories(),
            'statuses' => $this->statuses(),
            'filters' => $filters,
            'routePrefix' => $this->routePrefix($request),
            'initialTab' => $filters['tab'] ?? 'liste',
            'can_manage' => $user->isAdmin(),
            'threshold' => $threshold,
            'stats' => [
                'month_total' => (int) (clone $monthAgg)->sum('amount'),
                'month_count' => (clone $monthAgg)->count(),
                'pending_total' => (int) Expense::query()->where('status', ExpenseStatus::Pending->value)->sum('amount'),
                'pending_count' => Expense::query()->where('status', ExpenseStatus::Pending->value)->count(),
                'salary_month' => (int) (clone $monthAgg)->where('category', ExpenseCategory::Salaire->value)->sum('amount'),
            ],
            'evolution' => $evolution->handle('expense'),
        ]);
    }

    public function store(ExpenseRequest $request, AuditService $audit): RedirectResponse
    {
        $threshold = $this->threshold($request->user()->school);

        $expense = Expense::create([
            'school_id' => $request->user()->school_id,
            'category' => $request->validated('category'),
            'label' => $request->validated('label'),
            'amount' => $request->validated('amount'),
            'expense_date' => $request->validated('expense_date'),
            'justification' => $request->validated('justification'),
            'status' => $request->validated('amount') > $threshold
                ? ExpenseStatus::Pending
                : ExpenseStatus::Approved,
            'created_by' => $request->user()->id,
        ]);
        $expense->forceFill([
            'reference' => 'DEP-'.str_pad((string) $expense->id, 4, '0', STR_PAD_LEFT),
        ])->save();

        $audit->log('expense.created', $expense, [
            'category' => $expense->category->value,
            'amount' => $expense->amount,
            'status' => $expense->status->value,
        ]);

        return back()->with('success', $expense->status === ExpenseStatus::Pending
            ? 'Dépense enregistrée : elle doit être approuvée par l\'administrateur.'
            : 'Dépense enregistrée.');
    }

    public function approve(Request $request, Expense $expense, AuditService $audit): RedirectResponse
    {
        $this->assertAdmin($request);
        $this->assertSameSchool($request, $expense);

        abort_unless($expense->status === ExpenseStatus::Pending, 422, 'Cette dépense ne peut plus être approuvée.');

        $expense->forceFill([
            'status' => ExpenseStatus::Approved,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ])->save();

        $audit->log('expense.approved', $expense, ['amount' => $expense->amount]);

        return back()->with('success', 'Dépense approuvée.');
    }

    public function reject(Request $request, Expense $expense, AuditService $audit): RedirectResponse
    {
        $this->assertAdmin($request);
        $this->assertSameSchool($request, $expense);

        abort_unless($expense->status === ExpenseStatus::Pending, 422, 'Cette dépense ne peut plus être rejetée.');

        $expense->forceFill([
            'status' => ExpenseStatus::Rejected,
            'rejected_by' => $request->user()->id,
            'rejected_at' => now(),
        ])->save();

        $audit->log('expense.rejected', $expense, ['amount' => $expense->amount]);

        return back()->with('success', 'Dépense rejetée.');
    }

    public function destroy(Request $request, Expense $expense, AuditService $audit): RedirectResponse
    {
        $this->assertAdmin($request);
        $this->assertSameSchool($request, $expense);

        $expense->delete();
        $audit->log('expense.deleted', $expense, ['amount' => $expense->amount]);

        return back()->with('success', 'Dépense supprimée.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $this->assertAdmin($request);

        $data = $request->validate([
            'threshold' => ['required', 'integer', 'min:1000', 'max:1000000000'],
        ]);

        $setting = $request->user()->school->settingsOrNew();
        $options = $setting->options ?? [];
        $options['expense_approval_threshold'] = $data['threshold'];
        $setting->forceFill(['options' => $options])->save();

        return back()->with('success', 'Seuil d\'approbation des dépenses mis à jour.');
    }

    public function threshold(School $school): int
    {
        $options = $school->settingsOrNew()->options ?? [];

        return (int) ($options['expense_approval_threshold'] ?? 50000);
    }

    private function present(Expense $expense): array
    {
        return [
            'id' => $expense->id,
            'reference' => $expense->reference,
            'category' => $expense->category->value,
            'category_label' => $expense->category->label(),
            'label' => $expense->label,
            'amount' => $expense->amount,
            'expense_date' => $expense->expense_date->format('Y-m-d'),
            'status' => $expense->status->value,
            'status_label' => $expense->status->label(),
            'justification' => $expense->justification,
            'creator_name' => $expense->creator?->name,
        ];
    }

    private function categories(): array
    {
        return collect(ExpenseCategory::createable())->map(fn (ExpenseCategory $category) => [
            'value' => $category->value,
            'label' => $category->label(),
        ])->values()->all();
    }

    private function statuses(): array
    {
        return collect(ExpenseStatus::cases())->map(fn (ExpenseStatus $status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ])->values()->all();
    }

    private function assertAdmin(Request $request): void
    {
        abort_unless($request->user()->isAdmin(), 403);
    }

    private function assertSameSchool(Request $request, Expense $expense): void
    {
        abort_unless((int) $expense->school_id === (int) $request->user()->school_id, 403);
    }

    private function routePrefix(Request $request): string
    {
        return $request->user()->isAdmin() ? 'admin' : 'secretary';
    }
}