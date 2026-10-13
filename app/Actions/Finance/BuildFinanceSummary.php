<?php

namespace App\Actions\Finance;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\StudentContribution;

/**
 * Synthèse financière d'un établissement : recettes (ventes + encaissements de
 * contributions), dépenses validées et bénéfice qui en découle.
 *
 * Alimente la section « Bénéfice » du tableau de bord administrateur.
 */
class BuildFinanceSummary
{
    public function handle(): array
    {
        $sales = (int) Sale::query()->sum('total');
        $contributions = (int) StudentContribution::query()->sum('amount_paid');
        $expenses = (int) Expense::query()
            ->where('status', '!=', ExpenseStatus::Rejected->value)
            ->sum('amount');

        $revenue = $sales + $contributions;
        $profit = $revenue - $expenses;
        $margin = $revenue > 0 ? (int) round($profit / $revenue * 100) : 0;

        return [
            'sales' => $sales,
            'contributions' => $contributions,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'profit' => $profit,
            'margin' => $margin,
        ];
    }
}
