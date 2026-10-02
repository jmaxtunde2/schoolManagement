<?php

namespace App\Actions\Evaluations;

use App\Enums\EvaluationStatus;
use App\Models\Evaluation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaveGrades
{
    public function handle(
        Evaluation $evaluation,
        array $rows,
        int $userId
    ): void {
        if ($evaluation->status === EvaluationStatus::Validated) {
            throw new RuntimeException(
                'Cette évaluation est déjà validée.'
            );
        }

        DB::transaction(function () use (
            $evaluation,
            $rows,
            $userId
        ) {
            $allowedStudentIds = $evaluation
                ->results()
                ->pluck('student_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            foreach ($rows as $row) {
                $studentId = (int) $row['student_id'];

                if (! in_array($studentId, $allowedStudentIds, true)) {
                    throw new RuntimeException(
                        'Un élève fourni ne fait pas partie de cette évaluation.'
                    );
                }

                $isAbsent = (bool) ($row['is_absent'] ?? false);

                $evaluation
                    ->results()
                    ->where('student_id', $studentId)
                    ->update([
                        'score' => $isAbsent
                            ? null
                            : $row['score'],

                        'is_absent' => $isAbsent,

                        'updated_by' => $userId,
                    ]);
            }

            $evaluation->update([
                'status' => EvaluationStatus::Draft->value,
                'entered_by' => $userId,
            ]);
        });
    }
}