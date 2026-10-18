<?php

namespace App\Services\Assistant;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\StudentContribution;
use Illuminate\Support\Carbon;

/**
 * Situation financière de l'établissement : recettes, dépenses et solde.
 *
 * Le service est uniquement instancié lorsque le contexte autorise la
 * consultation des finances (`canViewFinance`) ; l'orchestrateur refuse
 * explicitement les rôles non habilités.
 */
class SchoolFinanceService
{
    public function __construct(private AssistantContext $context) {}

    /**
     * Bornes de la période demandée.
     *
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    public function range(string $scope): array
    {
        return match ($scope) {
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'year' => $this->context->academicYear
                ? [Carbon::parse($this->context->academicYear->starts_on)->startOfDay(), now()->endOfDay()]
                : [now()->startOfYear(), now()->endOfYear()],
            default => [null, null],
        };
    }

    public function revenue(?Carbon $from, ?Carbon $to): int
    {
        $sales = Sale::query()
            ->when($from && $to, fn ($query) => $query->whereBetween('sold_at', [$from, $to]))
            ->sum('total');

        $contributions = StudentContribution::query()
            ->whereNotNull('paid_at')
            ->when($from && $to, fn ($query) => $query->whereBetween('paid_at', [$from, $to]))
            ->sum('amount_paid');

        return (int) $sales + (int) $contributions;
    }

    public function expenses(?Carbon $from, ?Carbon $to): int
    {
        return (int) Expense::query()
            ->where('status', '!=', ExpenseStatus::Rejected->value)
            ->when($from && $to, fn ($query) => $query->whereBetween('expense_date', [$from, $to]))
            ->sum('amount');
    }

    /**
     * @return array{revenue: int, expenses: int, balance: int}
     */
    public function summary(string $scope): array
    {
        [$from, $to] = $this->range($scope);

        $revenue = $this->revenue($from, $to);
        $expenses = $this->expenses($from, $to);

        return ['revenue' => $revenue, 'expenses' => $expenses, 'balance' => $revenue - $expenses];
    }

    /** Montant total restant à recouvrer sur les contributions (impayés). */
    public function outstanding(): int
    {
        return (int) StudentContribution::query()
            ->selectRaw('COALESCE(SUM(amount_due - amount_paid), 0) as total')
            ->value('total');
    }

    /**
     * Dépenses validées regroupées par catégorie, décroissant.
     *
     * @return array<int, array{label: string, total: int}>
     */
    public function expensesByCategory(string $scope): array
    {
        [$from, $to] = $this->range($scope);

        $expenses = Expense::query()
            ->where('status', '!=', ExpenseStatus::Rejected->value)
            ->when($from && $to, fn ($query) => $query->whereBetween('expense_date', [$from, $to]))
            ->get(['category', 'amount']);

        return $expenses
            ->groupBy(fn (Expense $expense) => $expense->category->value)
            ->map(fn ($group) => [
                'label' => $group->first()->category->label(),
                'total' => (int) $group->sum('amount'),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }
}
