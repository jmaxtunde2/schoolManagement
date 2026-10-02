<?php

namespace App\Policies;

use App\Enums\ReportCardStatus;
use App\Models\ReportCard;
use App\Models\User;

class ReportCardPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCenseur() || $user->isSecretary() || $user->isTeacher();
    }

    public function view(User $user, ReportCard $reportCard): bool
    {
        if ((int) $reportCard->school_id !== (int) $user->school_id) {
            return false;
        }

        if ($user->isAdmin() || $user->isCenseur() || $user->isSecretary()) {
            return true;
        }

        if ($user->isTeacher()) {
            return $reportCard->class_room_id !== null
                && ($user->teacher?->classes()->where('classes.id', $reportCard->class_room_id)->exists() ?? false);
        }

        return $reportCard->status === ReportCardStatus::Published
            && $user->isParent()
            && ($user->parentGuardian?->students()->whereKey($reportCard->student_id)->exists() ?? false);
    }

    public function generate(User $user): bool
    {
        return $user->isAdmin() || $user->isCenseur() || $user->isSecretary();
    }

    public function updateComments(User $user, ReportCard $reportCard): bool
    {
        if (
            (int) $reportCard->school_id !== (int) $user->school_id
            || $reportCard->status === ReportCardStatus::Published
        ) {
            return false;
        }

        if ($user->isAdmin() || $user->isCenseur() || $user->isSecretary()) {
            return true;
        }

        return $user->isTeacher()
            && $reportCard->class_room_id !== null
            && ($user->teacher?->classes()->where('classes.id', $reportCard->class_room_id)->exists() ?? false);
    }

    public function publish(User $user, ReportCard $reportCard): bool
    {
        return (int) $reportCard->school_id === (int) $user->school_id
            && $reportCard->status === ReportCardStatus::Generated
            && ($user->isAdmin() || $user->isCenseur());
    }

    public function regenerate(User $user, ReportCard $reportCard): bool
    {
        return (int) $reportCard->school_id === (int) $user->school_id
            && $reportCard->status === ReportCardStatus::Published
            && ($user->isAdmin() || $user->isCenseur());
    }
}