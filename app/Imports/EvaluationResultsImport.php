<?php

namespace App\Imports;

use App\Enums\EvaluationStatus;
use App\Models\Evaluation;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use RuntimeException;

class EvaluationResultsImport implements ToCollection, WithHeadingRow, WithValidation
{
    public function __construct(
        private Evaluation $evaluation,
        private int $userId
    ) {
    }

    public function collection(Collection $rows): void
    {
        if ($this->evaluation->status === EvaluationStatus::Validated) {
            throw new RuntimeException('Cette évaluation est déjà validée.');
        }

        $allowedStudentIds = $this->evaluation
            ->results()
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $maxScore = (float) $this->evaluation->max_score;

        DB::transaction(function () use ($rows, $allowedStudentIds, $maxScore) {
            foreach ($rows as $row) {
                $matricule = trim((string) ($row['matricule'] ?? $row['Matricule'] ?? ''));
                if ($matricule === '') {
                    continue;
                }

                $student = Student::withoutGlobalScopes()
                    ->where('school_id', $this->evaluation->school_id)
                    ->where('matricule', $matricule)
                    ->first();

                if (! $student) {
                    continue;
                }

                $studentId = (int) $student->id;
                if (! in_array($studentId, $allowedStudentIds, true)) {
                    continue;
                }

                $presence = $row['presence'] ?? $row['Presence'] ?? $row['présence'] ?? $row['Présence'] ?? null;
                $presenceStr = is_string($presence) ? strtolower(trim($presence)) : '';
                $isAbsent = in_array($presenceStr, ['absent', 'absente'], true) || $presence === false;

                // Find score in any column that looks like a note/devoir
                $score = null;
                foreach ($row as $key => $value) {
                    $keyLower = is_string($key) ? strtolower($key) : '';
                    if (in_array($keyLower, ['matricule', 'nom et prenoms', 'nom_et_prenoms', 'nom et prénoms', 'presence', 'présence'], true)) {
                        continue;
                    }
                    if ($value === null || $value === '') {
                        continue;
                    }
                    $score = is_numeric($value) ? (float) $value : null;
                    if ($score !== null) {
                        break;
                    }
                }

                if ($isAbsent) {
                    $score = null;
                }

                if ($score !== null && ($score < 0 || $score > $maxScore)) {
                    continue;
                }

                $this->evaluation
                    ->results()
                    ->where('student_id', $studentId)
                    ->update([
                        'score' => $isAbsent ? null : $score,
                        'is_absent' => $isAbsent,
                        'updated_by' => $this->userId,
                    ]);
            }

            $status = $this->evaluation->status === EvaluationStatus::InProgress
                ? EvaluationStatus::InProgress->value
                : EvaluationStatus::Draft->value;

            $this->evaluation->update([
                'status' => $status,
                'entered_by' => $this->userId,
            ]);
        });
    }

    public function rules(): array
    {
        return [];
    }
}
