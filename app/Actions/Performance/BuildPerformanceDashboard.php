<?php

namespace App\Actions\Performance;

use App\Enums\AttendanceStatus;
use App\Enums\ExpenseStatus;
use App\Enums\Role;
use App\Models\AcademicPeriod;
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
 * Agrégats mensuels par catégorie de l'établissement, destinés à la page
 * « Performance » (graphiques SVG).
 *
 * La fenêtre démarre à la rentrée (premier mois de l'année scolaire) pour
 * afficher d'emblée la période active sans défilement ; à défaut de période
 * déclarée, on retombe sur les 12 derniers mois.
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
        $start = $this->windowStart($end);

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

    /**
     * Premier mois de la fenêtre : la rentrée (début de l'année scolaire en
     * cours) afin de montrer la période active sans défilement. On retombe sur
     * les 12 derniers mois si l'établissement n'a pas encore de période.
     */
    private function windowStart(Carbon $end): Carbon
    {
        $floor = $end->copy()->startOfMonth()->subMonths(self::MONTHS - 1);
        $schoolId = auth()->user()?->school_id;

        if ($schoolId === null) {
            return $floor;
        }

        $rentree = AcademicPeriod::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->whereNotNull('starts_at')
            ->orderBy('starts_at')
            ->value('starts_at');

        if ($rentree === null) {
            return $floor;
        }

        $start = Carbon::parse($rentree)->startOfMonth();

        return $start->greaterThan($floor) ? $start : $floor;
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
        $margin = $revenueSum > 0 ? (int) round($net / $revenueSum * 100) : 0;

        $profits = [];
        foreach ($revenues as $position => $value) {
            $profits[$position] = $value - $expenses['values'][$position];
        }

        return $this->category([
            'summary' => [
                [
                    'label' => 'Recettes',
                    'value' => $this->money($revenueSum),
                    'tone' => null,
                ],
                [
                    'label' => 'Dépenses',
                    'value' => $this->money($expenseSum),
                    'tone' => null,
                ],
                [
                    'label' => 'Bénéfice',
                    'value' => ($net >= 0 ? '+ ' : '- ').$this->money(abs($net)),
                    'tone' => $net >= 0 ? 'positive' : 'negative',
                ],
                [
                    'label' => 'Marge bénéficiaire',
                    'value' => $this->percent($margin),
                    'tone' => $margin >= 15 ? 'positive' : ($margin >= 0 ? 'neutral' : 'negative'),
                ],
            ],
            'series' => [
                ['label' => 'Recettes', 'color' => '#059669', 'values' => $revenues],
                ['label' => 'Sorties', 'color' => '#e11d48', 'values' => $expenses['values']],
                ['label' => 'Bénéfice', 'color' => '#2563eb', 'values' => array_values($profits)],
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function academique(): array
    {
        $records = AttendanceRecord::query()->get(['attendance_date', 'status']);
        $empty = array_fill(0, count($this->keys), 0);
        $counts = [
            AttendanceStatus::Present->value => $empty,
            AttendanceStatus::Absent->value => $empty,
            AttendanceStatus::Late->value => $empty,
            AttendanceStatus::Permission->value => $empty,
        ];

        foreach ($records as $record) {
            $position = $this->position($record->attendance_date);
            if ($position === null) {
                continue;
            }
            $counts[$record->status->value][$position]++;
        }

        $total = 0;
        foreach ($counts[AttendanceStatus::Present->value] as $position => $count) {
            $total += $count
                + $counts[AttendanceStatus::Absent->value][$position]
                + $counts[AttendanceStatus::Late->value][$position]
                + $counts[AttendanceStatus::Permission->value][$position];
        }
        $average = $total > 0 ? (int) round(array_sum($counts[AttendanceStatus::Present->value]) / $total * 100) : 0;

        return $this->category([
            'summary' => [
                ['label' => 'Assiduité moyenne', 'value' => $this->percent($average), 'tone' => $average >= 90 ? 'positive' : ($average >= 75 ? 'neutral' : 'negative')],
                ['label' => 'Relevés saisis', 'value' => (string) $total, 'tone' => null],
            ],
            'series' => [
                ['label' => 'Présents', 'color' => '#059669', 'values' => $counts[AttendanceStatus::Present->value]],
                ['label' => 'Absences', 'color' => '#e11d48', 'values' => $counts[AttendanceStatus::Absent->value]],
                ['label' => 'Retards', 'color' => '#f59e0b', 'values' => $counts[AttendanceStatus::Late->value]],
                ['label' => 'Permissions', 'color' => '#2563eb', 'values' => $counts[AttendanceStatus::Permission->value]],
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function eleves(): array
    {
        $students = $this->series(Student::query()->get(['created_at']), 'created_at', 'count');
        $parents = $this->series(ParentGuardian::query()->get(['created_at']), 'created_at', 'count');
        $guardians = ParentGuardian::query()->count();

        $total = array_sum($students['values']);

        return $this->category([
            'summary' => [
                ['label' => 'Élèves inscrits', 'value' => (string) $total, 'tone' => null],
                ['label' => 'Élèves actifs au total', 'value' => (string) Student::query()->where('is_active', true)->count(), 'tone' => null],
                ['label' => 'Parents', 'value' => (string) $guardians, 'tone' => null],
            ],
            'series' => [
                ['label' => 'Nouvelles inscriptions', 'color' => '#0ea5e9', 'values' => $students['values']],
                ['label' => 'Nouveaux parents', 'color' => '#8b5cf6', 'values' => $parents['values']],
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function personnel(): array
    {
        $schoolId = auth()->user()?->school_id;

        $staffQuery = fn () => User::query()
            ->whereIn('role', array_map(fn (Role $role) => $role->value, Role::assignableStaff()))
            ->when($schoolId !== null, fn ($query) => $query->where('school_id', $schoolId));

        $staff = $this->series($staffQuery()->get(['created_at']), 'created_at', 'count');

        return $this->category([
            'summary' => [
                ['label' => 'Membres recrutés', 'value' => (string) array_sum($staff['values']), 'tone' => null],
                ['label' => 'Effectif actuel', 'value' => (string) $staffQuery()->count(), 'tone' => null],
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
                ['label' => 'SMS envoyés', 'value' => (string) array_sum($sent['values']), 'tone' => null],
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
