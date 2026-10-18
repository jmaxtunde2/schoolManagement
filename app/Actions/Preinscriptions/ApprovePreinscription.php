<?php

namespace App\Actions\Preinscriptions;

use App\Enums\PreinscriptionStatus;
use App\Models\ParentGuardian;
use App\Models\Preinscription;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Validation d'une préinscription (phase 7).
 *
 * Crée l'élève, rattache un responsable (parent) et marque la demande comme
 * validée. Toute l'opération est transactionnelle afin qu'une demande validée
 * corresponde toujours à un élève existant.
 */
class ApprovePreinscription
{
    public function handle(Preinscription $preinscription, User $reviewer, ?int $classId): Student
    {
        return DB::transaction(function () use ($preinscription, $reviewer, $classId) {
            $student = Student::create([
                'school_id' => $preinscription->school_id,
                'class_id' => $classId,
                'first_name' => $preinscription->first_name,
                'last_name' => $preinscription->last_name,
                'birth_date' => $preinscription->birth_date,
                'gender' => $preinscription->gender,
                'is_active' => true,
            ]);

            $guardian = ParentGuardian::create([
                'school_id' => $preinscription->school_id,
                'name' => $preinscription->parent_name,
                'phone' => $preinscription->parent_phone,
                'email' => $preinscription->parent_email,
                'is_active' => true,
            ]);

            $student->guardians()->attach($guardian->id, [
                'relationship' => $preinscription->parent_relationship ?: 'Tuteur',
            ]);

            $preinscription->update([
                'status' => PreinscriptionStatus::Approved,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'student_id' => $student->id,
                'rejection_reason' => null,
            ]);

            return $student;
        });
    }
}
