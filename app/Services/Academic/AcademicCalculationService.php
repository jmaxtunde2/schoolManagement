<?php

namespace App\Services\Academic;

use App\Enums\AttendanceStatus;
use App\Enums\EvaluationStatus;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AcademicCalculationService
{
    public function calculateClassResults(
        ClassRoom $classRoom,
        AcademicPeriod $period,
        int $schoolId
    ): array
    {
        if ((int) $classRoom->school_id !== (int) $period->school_id) {
            throw new InvalidArgumentException('La classe et la période doivent appartenir à la même école.');
        }

        if (
            (int) $classRoom->school_id !== $schoolId
            || (int) $period->school_id !== $schoolId
            || ! AcademicYear::withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->whereKey($period->academic_year_id)
                ->exists()
        ) {
            throw new InvalidArgumentException('La classe ne fait pas partie de votre école.');
        }

        $pivot = DB::table('class_subject')
            ->where('class_id', $classRoom->id)
            ->get(['subject_id', 'default_coefficient'])
            ->keyBy('subject_id');

        $subjects = Subject::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->whereIn('id', $pivot->keys())
            ->orderBy('name')
            ->get(['id', 'name']);

        $validatedEvaluations = Evaluation::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('class_id', $classRoom->id)
            ->where('academic_year_id', $period->academic_year_id)
            ->where('academic_period_id', $period->id)
            ->where('status', EvaluationStatus::Validated->value)
            ->with(['results' => fn ($query) => $query
                ->where('school_id', $schoolId)
                ->where('is_absent', false)
                ->whereNotNull('score')
                ->getQuery()])
            ->get(['id', 'subject_id', 'coefficient', 'max_score']);

        $evaluationsBySubject = $validatedEvaluations
            ->groupBy('subject_id')
            ->map(fn (Collection $evaluations) => $evaluations->filter(
                fn (Evaluation $evaluation) => $evaluation->max_score > 0
            ));

        $students = Student::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('class_id', $classRoom->id)
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'matricule']);

        $attendanceByStudent = AttendanceRecord::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->whereIn('student_id', $students->pluck('id'))
            ->where(function ($query) use ($period) {
                $query->where('academic_period_id', $period->id);

                if ($period->starts_at && $period->ends_at) {
                    $query->orWhereBetween('attendance_date', [$period->starts_at, $period->ends_at]);
                }
            })
            ->select('student_id')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS absences', [AttendanceStatus::Absent->value])
            ->selectRaw('SUM(CASE WHEN status = ? AND justified_at IS NOT NULL THEN 1 ELSE 0 END) AS justified_absences', [AttendanceStatus::Absent->value])
            ->selectRaw('SUM(CASE WHEN status = ? AND justified_at IS NULL THEN 1 ELSE 0 END) AS unjustified_absences', [AttendanceStatus::Absent->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS late_count', [AttendanceStatus::Late->value])
            ->selectRaw('COALESCE(SUM(delay_minutes), 0) AS delay_minutes')
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        $results = $students->map(function (Student $student) use ($subjects, $evaluationsBySubject, $pivot, $attendanceByStudent) {
            $subjectResults = [];
            $weightedTotal = 0.0;
            $subjectCoefficientTotal = 0.0;
            $evaluationCount = 0;
            $attendance = $attendanceByStudent->get($student->id);

            foreach ($subjects as $subject) {
                $evaluations = $evaluationsBySubject->get($subject->id, collect());
                $weightedScore = 0.0;
                $evaluationCoefficientTotal = 0.0;
                $subjectEvaluationCount = 0;

                foreach ($evaluations as $evaluation) {
                    $result = $evaluation->results->firstWhere('student_id', $student->id);
                    if (! $result || $result->is_absent || $result->score === null) {
                        continue;
                    }

                    $evaluationCoefficient = max(0.0, (float) $evaluation->coefficient);
                    if ($evaluationCoefficient === 0.0) {
                        continue;
                    }

                    $normalizedScore = ((float) $result->score / (float) $evaluation->max_score) * 20;
                    $weightedScore += $normalizedScore * $evaluationCoefficient;
                    $evaluationCoefficientTotal += $evaluationCoefficient;
                    $subjectEvaluationCount++;
                }

                if ($subjectEvaluationCount === 0 || $evaluationCoefficientTotal === 0.0) {
                    continue;
                }

                $average = $weightedScore / $evaluationCoefficientTotal;
                $subjectCoefficient = max(0.0, (float) $pivot->get($subject->id)->default_coefficient);
                if ($subjectCoefficient === 0.0) {
                    continue;
                }

                $subjectResults[] = [
                    'subject_id' => $subject->id,
                    'subject_name' => $subject->name,
                    'average' => round($average, 2),
                    'coefficient' => $subjectCoefficient,
                    'evaluation_count' => $subjectEvaluationCount,
                    'teacher_comment' => null,
                    'appreciation' => $this->appreciation($average),
                ];

                $weightedTotal += $average * $subjectCoefficient;
                $subjectCoefficientTotal += $subjectCoefficient;
                $evaluationCount += $subjectEvaluationCount;
            }

            return [
                'student_id' => $student->id,
                'student_name' => $student->full_name,
                'matricule' => $student->matricule,
                'subjects' => $subjectResults,
                'general_average' => $subjectCoefficientTotal > 0
                    ? round($weightedTotal / $subjectCoefficientTotal, 2)
                    : null,
                'evaluation_count' => $evaluationCount,
                'rank' => null,
                'ranked_students' => null,
                'attendance' => [
                    'absences' => (int) ($attendance?->absences ?? 0),
                    'justified_absences' => (int) ($attendance?->justified_absences ?? 0),
                    'unjustified_absences' => (int) ($attendance?->unjustified_absences ?? 0),
                    'late_count' => (int) ($attendance?->late_count ?? 0),
                    'delay_minutes' => (int) ($attendance?->delay_minutes ?? 0),
                ],
            ];
        });

        $ranked = $results
            ->filter(fn (array $result) => $result['general_average'] !== null)
            ->sortByDesc('general_average')
            ->values();

        $rankByStudent = [];
        $previousAverage = null;
        $currentRank = null;
        foreach ($ranked as $index => $result) {
            if ($previousAverage === null || $result['general_average'] !== $previousAverage) {
                $currentRank = $index + 1;
                $previousAverage = $result['general_average'];
            }

            $rankByStudent[$result['student_id']] = $currentRank;
        }

        $rankedStudentCount = $ranked->count();
        $results = $results->map(function (array $result) use ($rankedStudentCount, $rankByStudent) {
            $result['rank'] = $rankByStudent[$result['student_id']] ?? null;
            $result['ranked_students'] = $result['general_average'] !== null
                ? $rankedStudentCount
                : null;

            return $result;
        });

        return [
            'school_id' => $schoolId,
            'class_room_id' => $classRoom->id,
            'class_name' => $classRoom->name,
            'academic_year_id' => $period->academic_year_id,
            'academic_year_name' => $period->academicYear()->value('name'),
            'period_id' => $period->id,
            'period_name' => $period->name,
            'students' => $results->values(),
            'ranked_students' => $rankedStudentCount,
        ];
    }

    public function appreciation(float $average): string
    {
        return match (true) {
            $average >= 16 => 'Très satisfaisant',
            $average >= 14 => 'Satisfaisant',
            $average >= 12 => 'Assez satisfaisant',
            $average >= 10 => 'Passable',
            default => 'Insuffisant',
        };
    }

}