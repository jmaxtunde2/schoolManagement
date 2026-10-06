<?php

namespace App\Policies;

use App\Models\Timetable;
use App\Models\User;

/**
 * Droits sur l'emploi du temps.
 *
 * La lecture est ouverte à tous les rôles du personnel (l'enseignant voit son
 * propre emploi du temps, pas celui des autres) ; l'écriture est réservée à
 * l'administrateur de l'établissement. Les parents n'ont pas accès au modèle
 * mais passent par Parent\TimetableController, qui filtre sur leurs enfants.
 */
class TimetablePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Timetable $timetable): bool
    {
        return $this->sameSchool($user, $timetable);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() && $user->school_id !== null;
    }

    public function update(User $user, Timetable $timetable): bool
    {
        return $user->isAdmin() && $this->sameSchool($user, $timetable);
    }

    public function delete(User $user, Timetable $timetable): bool
    {
        return $user->isAdmin() && $this->sameSchool($user, $timetable);
    }

    private function sameSchool(User $user, Timetable $timetable): bool
    {
        return $user->school_id !== null
            && (int) $timetable->school_id === (int) $user->school_id;
    }
}
