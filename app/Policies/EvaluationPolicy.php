<?php

namespace App\Policies;

use App\Enums\EvaluationStatus;
use App\Models\Evaluation;
use App\Models\User;

class EvaluationPolicy
{
    /**
     * Consultation d'une évaluation.
     */
    public function view(
        User $user,
        Evaluation $evaluation
    ): bool {
        if (
            (int) $evaluation->school_id !==
            (int) $user->school_id
        ) {
            return false;
        }

        return $user->isAdmin()
            || $user->isCenseur()
            || $user->isSecretary()
            || $evaluation->teacher_id === $user->teacher?->id;
    }

    /**
     * Modification des notes.
     *
     * Autorisée uniquement :
     * - par l'enseignant responsable ;
     * - ou par le secrétaire ;
     * - lorsque l'évaluation est un brouillon
     *   ou a été retournée pour correction.
     */
    public function update(
        User $user,
        Evaluation $evaluation
    ): bool {
        if (
            (int) $evaluation->school_id !==
            (int) $user->school_id
        ) {
            return false;
        }

        if (
            ! in_array(
                $evaluation->status,
                [
                    EvaluationStatus::Draft,
                    EvaluationStatus::Returned,
                ],
                true
            )
        ) {
            return false;
        }

        if ($user->isTeacher()) {
            return $evaluation->teacher_id === $user->teacher?->id;
        }

        return $user->isSecretary();
    }

    /**
     * Soumission d'une évaluation.
     *
     * Une évaluation retournée peut être corrigée
     * puis soumise à nouveau.
     */
    public function submit(
        User $user,
        Evaluation $evaluation
    ): bool {
        return $this->update($user, $evaluation);
    }

    /**
     * Validation définitive.
     *
     * Seuls l'administrateur et le censeur peuvent
     * valider une évaluation en attente.
     */
    public function validateEvaluation(
        User $user,
        Evaluation $evaluation
    ): bool {
        if (
            (int) $evaluation->school_id !==
            (int) $user->school_id
        ) {
            return false;
        }

        if (
            ! $user->isAdmin()
            && ! $user->isCenseur()
        ) {
            return false;
        }

        return $evaluation->status === EvaluationStatus::InProgress;
    }

    /**
     * Retourner une évaluation pour correction.
     *
     * L'administration ne modifie pas les notes.
     * Elle peut uniquement demander une correction
     * lorsque l'évaluation est en attente de validation.
     */
    public function returnEvaluation(
        User $user,
        Evaluation $evaluation
    ): bool {
        if (
            (int) $evaluation->school_id !==
            (int) $user->school_id
        ) {
            return false;
        }

        if (
            ! $user->isAdmin()
            && ! $user->isCenseur()
        ) {
            return false;
        }

        return $evaluation->status === EvaluationStatus::InProgress;
    }
}