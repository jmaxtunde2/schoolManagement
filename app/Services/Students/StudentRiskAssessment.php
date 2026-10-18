<?php

namespace App\Services\Students;

use App\Models\AcademicPeriod;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Services\Academic\AcademicCalculationService;
use App\Services\Assistant\AssistantContext;
use Throwable;

/**
 * Évaluation des élèves nécessitant une attention particulière (phase 5).
 *
 * Combine plusieurs signaux purement scolaires : moyenne faible, baisse des
 * résultats, difficultés multiples, absences et retards répétés. Les seuils
 * sont configurables (config/alerts.php > difficulty).
 *
 * Important : ces indicateurs sont un outil de pilotage pédagogique. Ils ne
 * constituent en aucun cas un diagnostic médical ou psychologique.
 */
class StudentRiskAssessment
{
    public function __construct(private AcademicCalculationService $calculator) {}

    /**
     * Élèves à suivre, triés du plus préoccupant au moins préoccupant.
     *
     * @return array<int, array<string, mixed>>
     */
    public function handle(AssistantContext $context): array
    {
        $entries = array_values(array_filter(
            $this->assess($context),
            fn (array $entry) => $entry['needs_attention'],
        ));

        usort($entries, fn (array $a, array $b) => $b['severity'] <=> $a['severity']);

        return $entries;
    }

    /** @return array<string, mixed>|null */
    public function forStudent(AssistantContext $context, Student $student): ?array
    {
        foreach ($this->assess($context) as $entry) {
            if ((int) $entry['student_id'] === (int) $student->id) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function assess(AssistantContext $context): array
    {
        $period = $context->currentPeriod;

        if ($period === null) {
            return [];
        }

        $thresholds = config('alerts.difficulty');
        $current = $this->resultsFor($context, $period);
        $previousAverages = $this->averagesFor($context, $this->previousPeriod($context, $period));

        $entries = [];

        foreach ($current as $classId => $result) {
            foreach ($result['students'] as $student) {
                $entries[] = $this->assessStudent($student, $result['class_name'], $classId, $previousAverages, $thresholds);
            }
        }

        return $entries;
    }

    /**
     * @param  array<string, mixed>  $student
     * @param  array<int, float>  $previousAverages
     * @param  array<string, mixed>  $thresholds
     * @return array<string, mixed>
     */
    private function assessStudent(array $student, string $className, int $classId, array $previousAverages, array $thresholds): array
    {
        $average = $student['general_average'] !== null ? (float) $student['general_average'] : null;
        $absences = (int) ($student['attendance']['absences'] ?? 0);
        $lates = (int) ($student['attendance']['late_count'] ?? 0);
        $delayMinutes = (int) ($student['attendance']['delay_minutes'] ?? 0);

        $weakSubjects = collect($student['subjects'])
            ->filter(fn (array $subject) => (float) $subject['average'] < $thresholds['low_average'])
            ->map(fn (array $subject) => [
                'subject' => $subject['subject_name'],
                'average' => (float) $subject['average'],
            ])
            ->sortBy('average')
            ->values()
            ->all();

        $allSubjects = collect($student['subjects'])
            ->map(fn (array $subject) => [
                'subject' => $subject['subject_name'],
                'average' => (float) $subject['average'],
                'below' => (float) $subject['average'] < $thresholds['low_average'],
            ])
            ->sortBy('average')
            ->values()
            ->all();

        $previousAverage = $previousAverages[$student['student_id']] ?? null;
        $dropped = $previousAverage !== null
            && $average !== null
            && ($previousAverage - $average) >= (float) $thresholds['drop_points'];

        $flags = [];
        $severity = 0;

        if ($average !== null && $average < (float) $thresholds['low_average']) {
            $flags[] = 'Moyenne faible';
            $severity += 3;
        }

        if ($dropped) {
            $flags[] = 'Baisse des résultats';
            $severity += 2;
        }

        if (count($weakSubjects) >= (int) $thresholds['weak_subject_min']) {
            $flags[] = 'Difficultés multiples';
            $severity += 2;
        }

        if ($absences >= (int) $thresholds['absences_min']) {
            $flags[] = 'Absences répétées';
            $severity += 3;
        }

        if ($lates >= (int) $thresholds['lates_min']) {
            $flags[] = 'Retards répétés';
            $severity += 1;
        }

        return [
            'student_id' => (int) $student['student_id'],
            'student_name' => $student['student_name'],
            'matricule' => $student['matricule'] ?? null,
            'class_id' => $classId,
            'class_name' => $className,
            'average' => $average,
            'previous_average' => $previousAverage,
            'drop' => $dropped,
            'absences' => $absences,
            'lates' => $lates,
            'delay_minutes' => $delayMinutes,
            'weak_subjects' => $weakSubjects,
            'subjects' => $allSubjects,
            'flags' => $flags,
            'needs_attention' => $flags !== [],
            'severity' => $severity,
            'performance_level' => $this->performanceLevel($average, $dropped, $thresholds),
            'attendance_level' => $this->attendanceLevel($absences, $lates, $thresholds),
        ];
    }

    private function performanceLevel(?float $average, bool $dropped, array $thresholds): string
    {
        if ($average === null) {
            return 'none';
        }

        if ($average < (float) $thresholds['low_average']) {
            return 'red';
        }

        return $dropped ? 'amber' : 'green';
    }

    private function attendanceLevel(int $absences, int $lates, array $thresholds): string
    {
        if ($absences >= (int) $thresholds['absences_min']) {
            return 'red';
        }

        return $lates >= (int) $thresholds['lates_min'] ? 'amber' : 'green';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resultsFor(AssistantContext $context, AcademicPeriod $period): array
    {
        $results = [];

        foreach ($context->classes() as $class) {
            try {
                $results[(int) $class->id] = $this->calculator->calculateClassResults($class, $period, $context->schoolId);
            } catch (Throwable) {
                continue;
            }
        }

        return $results;
    }

    /**
     * @return array<int, float>
     */
    private function averagesFor(AssistantContext $context, ?AcademicPeriod $period): array
    {
        if ($period === null) {
            return [];
        }

        $averages = [];

        foreach ($this->resultsFor($context, $period) as $result) {
            foreach ($result['students'] as $student) {
                if ($student['general_average'] !== null) {
                    $averages[(int) $student['student_id']] = (float) $student['general_average'];
                }
            }
        }

        return $averages;
    }

    private function previousPeriod(AssistantContext $context, AcademicPeriod $current): ?AcademicPeriod
    {
        $year = $context->academicYear;

        if ($year === null) {
            return null;
        }

        return AcademicPeriod::withoutGlobalScopes()
            ->where('school_id', $context->schoolId)
            ->where('academic_year_id', $year->id)
            ->where('position', '<', $current->position)
            ->orderByDesc('position')
            ->first();
    }
}
