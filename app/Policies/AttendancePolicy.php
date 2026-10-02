<?php

namespace App\Policies;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\ClassRoom;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin()
            || $user->isCenseur()
            || $user->isSecretary()
            || $user->isTeacher();
    }

    public function view(User $user, AttendanceRecord $record): bool
    {
        if ((int) $record->school_id !== (int) $user->school_id) {
            return false;
        }

        if ($user->isAdmin() || $user->isCenseur() || $user->isSecretary()) {
            return true;
        }

        if ($user->isTeacher()) {
            return $this->teacherCanAccessClass($user, (int) $record->class_room_id);
        }

        return $user->isParent()
            && $user->parentGuardian?->students()->whereKey($record->student_id)->exists();
    }

    public function create(User $user, ClassRoom $classRoom): bool
    {
        if ((int) $classRoom->school_id !== (int) $user->school_id) {
            return false;
        }

        if ($user->isAdmin() || $user->isCenseur() || $user->isSecretary()) {
            return true;
        }

        return $user->isTeacher()
            && $this->teacherCanAccessClass($user, $classRoom->id);
    }

    public function update(User $user, AttendanceRecord $record): bool
    {
        if ((int) $record->school_id !== (int) $user->school_id) {
            return false;
        }

        if ($user->isAdmin() || $user->isCenseur() || $user->isSecretary()) {
            return true;
        }

        if (! $user->isTeacher() || ! $this->teacherCanAccessClass($user, (int) $record->class_room_id)) {
            return false;
        }

        return ! $record->period?->is_closed;
    }

    public function justify(User $user, AttendanceRecord $record): bool
    {
        return (int) $record->school_id === (int) $user->school_id
            && $record->status === AttendanceStatus::Absent
            && ($user->isAdmin() || $user->isCenseur() || $user->isSecretary());
    }

    public function submitJustification(User $user, AttendanceRecord $record): bool
    {
        return $user->isParent()
            && (int) $record->school_id === (int) $user->school_id
            && $record->status === AttendanceStatus::Absent
            && $record->justified_at === null
            && ! $record->justifications()
                ->where('status', AttendanceJustificationStatus::Approved->value)
                ->exists()
            && $user->parentGuardian?->students()->whereKey($record->student_id)->exists();
    }

    public function delete(User $user, AttendanceRecord $record): bool
    {
        return (int) $record->school_id === (int) $user->school_id
            && ($user->isAdmin() || $user->isCenseur());
    }

    private function teacherCanAccessClass(User $user, int $classRoomId): bool
    {
        return $user->teacher?->classes()
            ->where('classes.id', $classRoomId)
            ->exists() ?? false;
    }
}