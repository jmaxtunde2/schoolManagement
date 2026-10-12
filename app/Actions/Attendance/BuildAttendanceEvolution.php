<?php

namespace App\Actions\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use Illuminate\Support\Carbon;

/**
 * Évolution des retards et des absences sur une période, agrégée par jour,
 * semaine ou mois — utilisée par le graphique de la page « Absences & retards ».
 *
 * L'agrégation est faite en PHP à partir des seuls couples
 * (attendance_date, status, delay_minutes) afin de rester portable entre SQLite
 * (tests) et MySQL (production), sans dépendre de fonctions de date propres à
 * un moteur.
 */
class BuildAttendanceEvolution
{
    public const GRANULARITIES = ['day', 'week', 'month', 'custom'];

    /**
     * Nombre de buckets par défaut pour chaque granularité simple.
     */
    private const DEFAULT_SPAN = [
        'day' => 14,
        'week' => 12,
        'month' => 12,
    ];

    public function handle(string $granularity, ?string $start = null, ?string $end = null): array
    {
        $granularity = in_array($granularity, self::GRANULARITIES, true) ? $granularity : 'day';

        $end = $this->parse($end) ?? Carbon::today();
        $start = $this->parse($start);

        if ($start === null) {
            $start = match ($granularity) {
                'week' => $end->copy()->startOfWeek()->subWeeks(self::DEFAULT_SPAN['week'] - 1),
                'month' => $end->copy()->startOfMonth()->subMonths(self::DEFAULT_SPAN['month'] - 1),
                default => $end->copy()->subDays(self::DEFAULT_SPAN['day'] - 1),
            };
        }

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy(), $start->copy()];
        }

        $unit = $this->unit($granularity, $start, $end);
        $buckets = $this->buckets($unit, $start, $end);

        $records = AttendanceRecord::query()
            ->whereIn('status', [
                AttendanceStatus::Late->value,
                AttendanceStatus::Absent->value,
            ])
            ->whereDate('attendance_date', '>=', $start->toDateString())
            ->whereDate('attendance_date', '<=', $end->toDateString())
            ->get(['attendance_date', 'status', 'delay_minutes']);

        $index = [];
        foreach ($buckets as $position => $bucket) {
            $index[$bucket['key']] = $position;
        }

        $totals = ['absences' => 0, 'retards' => 0, 'delay_minutes' => 0];

        foreach ($records as $record) {
            $key = $this->keyFor($unit, $record->attendance_date);
            if (! isset($index[$key])) {
                continue;
            }

            $position = $index[$key];

            if ($record->status === AttendanceStatus::Late) {
                $buckets[$position]['retards']++;
                $totals['retards']++;
                $totals['delay_minutes'] += (int) $record->delay_minutes;
            } else {
                $buckets[$position]['absences']++;
                $totals['absences']++;
            }
        }

        $max = 0;
        foreach ($buckets as $bucket) {
            $max = max($max, $bucket['absences'] + $bucket['retards']);
        }

        return [
            'granularity' => $granularity,
            'unit' => $unit,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'buckets' => array_values($buckets),
            'totals' => $totals,
            'max' => $max,
        ];
    }

    /**
     * Granularité effective : un intervalle personnalisé très large bascule
     * automatiquement en semaine ou en mois pour rester lisible.
     */
    private function unit(string $granularity, Carbon $start, Carbon $end): string
    {
        if ($granularity !== 'custom') {
            return $granularity;
        }

        $days = $start->diffInDays($end);

        return match (true) {
            $days <= 45 => 'day',
            $days <= 220 => 'week',
            default => 'month',
        };
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    private function buckets(string $unit, Carbon $start, Carbon $end): array
    {
        $buckets = [];
        $cursor = match ($unit) {
            'week' => $start->copy()->startOfWeek(),
            'month' => $start->copy()->startOfMonth(),
            default => $start->copy(),
        };

        $guard = 0;
        while ($cursor->lessThanOrEqualTo($end) && $guard++ < 500) {
            $buckets[] = [
                'key' => $this->keyFor($unit, $cursor),
                'label' => $this->labelFor($unit, $cursor),
                'absences' => 0,
                'retards' => 0,
            ];

            $cursor = match ($unit) {
                'week' => $cursor->copy()->addWeek(),
                'month' => $cursor->copy()->addMonth(),
                default => $cursor->copy()->addDay(),
            };
        }

        return $buckets;
    }

    private function keyFor(string $unit, Carbon $date): string
    {
        return match ($unit) {
            'week' => $date->format('o-\WW'),
            'month' => $date->format('Y-m'),
            default => $date->format('Y-m-d'),
        };
    }

    private function labelFor(string $unit, Carbon $date): string
    {
        $months = [
            1 => 'janv.', 2 => 'févr.', 3 => 'mars', 4 => 'avr.', 5 => 'mai', 6 => 'juin',
            7 => 'juil.', 8 => 'août', 9 => 'sept.', 10 => 'oct.', 11 => 'nov.', 12 => 'déc.',
        ];

        return match ($unit) {
            'week' => 'S'.$date->isoWeek(),
            'month' => $months[$date->month].' '.$date->format('y'),
            default => $date->format('d/m'),
        };
    }

    private function parse(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
