<?php

namespace App\Actions\Performance;

use App\Enums\AttendanceStatus;
use App\Enums\ExpenseStatus;
use App\Enums\Role;
use App\Models\AttendanceRecord;
use App\Models\Expense;
use App\Models\Notification;
use App\Models\ParentGuardian;
use App\Models\Sale;
use App\Models\Student;
use App\Models\StudentContribution;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Agrégats mensuels (12 derniers mois) par catégorie de l'établissement,
 * destinés à la page « Performance » (graphiques SVG).
 *
 * L'agrégation est faite en PHP à partir des seuls couples (date, montant) afin
 * de rester portable entre SQLite (tests) et MySQL (production).
 */
class BuildPerformanceDashboard
{
    private const MONTHS = 12;

    /** @var array<string, int> */
    private array $index = [];

    /** @var array<int, string> */
    private array $keys = [];

    /** @var array<int, string> */
    private array $labels = [];

    /** @return array<string, array<string, mixed>> */
    public function handle(): array
    {
        $end = Carbon::today()->endOfMonth();
        $start = $end->copy()->startOfMonth()->subMonths(self::MONTHS - 1);

        $cursor = $start->copy();
        while ($cursor->lessThanOrEqualTo($end)) {
            $key = $cursor->format('Y-m');
            $this->index[$key] = count($this->keys);
            $this->keys[] = $key;
            $this->labels[] = $this->label($cursor);
            $cursor = $cursor->copy()->addMonth();
        }

        return [
            'finance' => $this->finance(),
            'academique' => $this->academique(),
            'eleves' => $this->eleves(),
            'personnel' => $this->personnel(),
            'communication' => $this->communication(),
        ];
    }

    /** @return array<string, mixed> */
    private function finance(): array
    {
        $sales = $this->series(Sale::query()->get(['sold_at', 'total']), 'sold_at', 'total');
        $contributions = $this->series(
            StudentContribution::query()->whereNotNull('paid_at')->get(['paid_at', 'amount_paid']),
            'paid_at',
            'amount_paid'
        );
        $expenses = $this->series(
            Expense::query()->where('status', '!=', ExpenseStatus::Rejected->value)->get(['expense_date', 'amount']),
            'expense_date',
            'amount'
        );

        $revenues = array_map(fn ($value) => $value, $sales['values']);
        foreach ($contributions['values'] as $position => $value) {
            $revenues[$position] += $value;
        }

        $revenueSum = array_sum($revenues);
        $expenseSum = array_sum($expenses['values']);
        $net = $revenueSum - $expenseSum;

        return $this->category([
            'summary' => [
                [
                    'label' => 'Recettes (12 mois)',
                    'value' => $this->money($revenueSum),
                    'tone' => null,
                ],
                [
                    'label' => 'Sorties (12 mois)',
                    'value' => $this->money($expenseSum),
                    'tone' => null,
                ],
                [
                    'label' => 'Actif net',
                    'value' => ($net >= 0 ? '+ ' : '- ').$this->money(abs($net)),
                    'tone' => $net >= 0 ? 'positive' : 'negative',
                ],
            ],
            'series' => [
                ['label' => 'Recettes', 'color' => '#059669', 'values' => $revenues],
                ['label' => 'Sorties', 'color' => '#e11d48', 'values' => $expenses['values']],
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function academique(): array
    {
        $records = AttendanceRecord::query()->get(['attendance_date', 'status']);
        $total = array_fill(0, count($this->keys), 0);
        $present = array_fill(0, count($this->keys), 0);

        foreach ($records as $record) {
            $position = $this->position($record->attendance_date);
            if ($position === null) {
                continue;
            }
            $total[$position]++;
            if ($record->status === AttendanceStatus::Present) {
                $present[$position]++;
            }
        }

        $rates = [];
        $populated = [];
        foreach ($total as $position => $count) {
            $rates[] = $count > 0 ? (int) round($present[$position] / $count * 100) : 0;
            if ($count > 0) {
                $populated[] = (int) round($present[$position] / $count * 100);
            }
        }

        $average = $populated ? (int) round(array_sum($populated) / count($populated)) : 0;

        return $this->category([
            'summary' => [
                ['label' => 'Assiduité moyenne (12 mois)', 'value' => $this->percent($average), 'tone' => $average >= 90 ? 'positive' : ($average >= 75 ? 'neutral' : 'negative')],
                ['label' => 'Registres saisis', 'value' => (string) array_sum($total), 'tone' => null],
            ],
            'series' => [
                ['label' => 'Assiduité', 'color' => '#7c3aed', 'values' => $rates],
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function eleves(): array
    {
        $students = $this->series(Student::query()->get(['created_at']), 'created_at', 'count');
        $guardians = ParentGuardian::query()->count();

        $total = array_sum($students['values']);

        return $this->category([
            'summary' => [
                ['label' => 'Élèves inscrits (12 mois)', 'value' => (string) $total, 'tone' => null],
                ['label' => 'Élèves actifs au total', 'value' => (string) Student::query()->where('is_active', true)->count(), 'tone' => null],
                ['label' => 'Parents', 'value' => (string) $guardians, 'tone' => null],
            ],
            'series' => [
                ['label' => 'Nouvelles inscriptions', 'color' => '#0ea5e9', 'values' => $students['values']],
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function personnel(): array
    {
        $staff = $this->series(
            User::query()->whereIn('role', array_map(fn (Role $role) => $role->value, Role::assignableStaff()))->get(['created_at']),
            'created_at',
            'count'
        );

        return $this->category([
            'summary' => [
                ['label' => 'Membres recrutés (12 mois)', 'value' => (string) array_sum($staff['values']), 'tone' => null],
                ['label' => 'Effectif actuel', 'value' => (string) User::query()->whereIn('role', array_map(fn (Role $role) => $role->value, Role::assignableStaff()))->count(), 'tone' => null],
            ],
            'series' => [
                ['label' => 'Recrutements', 'color' => '#f97316', 'values' => $staff['values']],
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function communication(): array
    {
        $sent = $this->series(
            Notification::query()->whereIn('status', ['sent', 'delivered'])->get(['created_at']),
            'created_at',
            'count'
        );
        $failed = Notification::query()->where('status', 'failed')->count();

        return $this->category([
            'summary' => [
                ['label' => 'SMS envoyés (12 mois)', 'value' => (string) array_sum($sent['values']), 'tone' => null],
                ['label' => 'Échecs sur la période', 'value' => (string) $failed, 'tone' => $failed > 0 ? 'negative' : 'positive'],
            ],
            'series' => [
                ['label' => 'SMS envoyés', 'color' => '#f59e0b', 'values' => $sent['values']],
            ],
        ]);
    }

    /**
     * Série mensuelle alignée sur les 12 derniers mois.
     *
     * @param  iterable<object>  $rows
     * @return array{values: array<int, int>}
     */
    private function series(iterable $rows, string $dateField, string $valueField): array
    {
        $values = array_fill(0, count($this->keys), 0);

        foreach ($rows as $row) {
            $position = $this->position($row->{$dateField});
            if ($position === null) {
                continue;
            }
            if ($valueField === 'count') {
                $values[$position]++;
            } else {
                $values[$position] += (int) $row->{$valueField};
            }
        }

        return ['values' => $values];
    }

    /** @return array<string, mixed> */
    private function category(array $payload): array
    {
        return array_merge([
            'buckets' => array_map(fn ($key, $label) => ['key' => $key, 'label' => $label], $this->keys, $this->labels),
        ], $payload);
    }

    private function position(Carbon|string $date): ?int
    {
        $key = Carbon::parse($date)->format('Y-m');

        return $this->index[$key] ?? null;
    }

    private function money(int $value): string
    {
        return number_format($value, 0, ',', ' ').' F';
    }

    private function percent(int $value): string
    {
        return $value.' %';
    }

    private function label(Carbon $date): string
    {
        $months = [
            1 => 'janv.', 2 => 'févr.', 3 => 'mars', 4 => 'avr.', 5 => 'mai', 6 => 'juin',
            7 => 'juil.', 8 => 'août', 9 => 'sept.', 10 => 'oct.', 11 => 'nov.', 12 => 'déc.',
        ];

        return $months[$date->month].' '.$date->format('y');
    }
}