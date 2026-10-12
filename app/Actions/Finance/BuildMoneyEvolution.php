<?php

namespace App\Actions\Finance;

use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\StudentContribution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Évolution mensuelle d'une grandeur financière (dépenses, ventes, encaissements
 * de contributions) sur les douze derniers mois — alimente les graphiques SVG des
 * pages Finance.
 *
 * L'agrégation est faite en PHP à partir des seuls couples (date, montant) afin de
 * rester portable entre SQLite (tests) et MySQL (production).
 */
class BuildMoneyEvolution
{
    private const MONTHS = 12;

    public function handle(string $kind, ?string $start = null, ?string $end = null): array
    {
        $end = $this->parse($end) ?? Carbon::today()->endOfMonth();
        $start = $this->parse($start) ?? $end->copy()->startOfMonth()->subMonths(self::MONTHS - 1);
        $start = $start->copy()->startOfMonth();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfMonth(), $start->copy()->endOfMonth()];
        }

        $buckets = [];
        $index = [];
        $cursor = $start->copy();
        $guard = 0;
        while ($cursor->lessThanOrEqualTo($end) && $guard++ < (self::MONTHS * 2 + 24)) {
            $key = $cursor->format('Y-m');
            $index[$key] = count($buckets);
            $buckets[] = [
                'key' => $key,
                'label' => $this->label($cursor),
                'value' => 0,
                'count' => 0,
            ];
            $cursor = $cursor->copy()->addMonth();
        }

        foreach ($this->query($kind)->get(['date', 'amount']) as $row) {
            $key = Carbon::parse($row->date)->format('Y-m');
            if (! isset($index[$key])) {
                continue;
            }
            $position = $index[$key];
            $buckets[$position]['value'] += (int) $row->amount;
            $buckets[$position]['count']++;
        }

        $max = 0;
        $totals = ['value' => 0, 'count' => 0];
        foreach ($buckets as $bucket) {
            $max = max($max, $bucket['value']);
            $totals['value'] += $bucket['value'];
            $totals['count'] += $bucket['count'];
        }

        return [
            'kind' => $kind,
            'granularity' => 'month',
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'buckets' => $buckets,
            'totals' => $totals,
            'max' => $max,
        ];
    }

    /**
     * @return Builder<Expense|Sale|StudentContribution>
     */
    private function query(string $kind): Builder
    {
        return match ($kind) {
            'sale' => Sale::query()->selectRaw('sold_at as date, total as amount'),
            'contribution' => StudentContribution::query()
                ->whereNotNull('paid_at')
                ->selectRaw('paid_at as date, amount_paid as amount'),
            'salary' => Expense::query()
                ->where('category', ExpenseCategory::Salaire->value)
                ->where('status', '!=', ExpenseStatus::Rejected->value)
                ->selectRaw('expense_date as date, amount as amount'),
            default => Expense::query()
                ->where('status', '!=', ExpenseStatus::Rejected->value)
                ->selectRaw('expense_date as date, amount as amount'),
        };
    }

    private function label(Carbon $date): string
    {
        $months = [
            1 => 'janv.', 2 => 'févr.', 3 => 'mars', 4 => 'avr.', 5 => 'mai', 6 => 'juin',
            7 => 'juil.', 8 => 'août', 9 => 'sept.', 10 => 'oct.', 11 => 'nov.', 12 => 'déc.',
        ];

        return $months[$date->month].' '.$date->format('y');
    }

    private function parse(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}