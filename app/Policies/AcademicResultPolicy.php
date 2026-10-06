<?php

namespace App\Policies;

use App\Models\AcademicPeriod;
use App\Models\ClassRoom;
use App\Models\User;

class AcademicResultPolicy
{
    public function viewAcademicResults(User $user, ClassRoom $classRoom, AcademicPeriod $period): bool
    {
        if (
            (int) $classRoom->school_id !== (int) $user->school_id
            || (int) $period->school_id !== (int) $user->school_id
            || (int) $period->academic_year_id !== (int) $classRoom->school->academicYears()->whereKey($period->academic_year_id)->value('id')
        ) {
            return false;
        }

        if ($user->isAdmin() || $user->isCenseur() || $user->isSecretary()) {
            return true;
        }

        return $user->isTeacher()
            && ($user->teacher?->classes()->where('classes.id', $classRoom->id)->exists() ?? false);
    }
}
