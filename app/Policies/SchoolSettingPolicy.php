<?php

namespace App\Policies;

use App\Models\SchoolSetting;
use App\Models\User;

class SchoolSettingPolicy
{
    public function view(User $user, SchoolSetting $setting): bool
    {
        return $user->isAdmin() && $this->sameSchool($user, $setting);
    }

    public function update(User $user, SchoolSetting $setting): bool
    {
        return $user->isAdmin() && $this->sameSchool($user, $setting);
    }

    private function sameSchool(User $user, SchoolSetting $setting): bool
    {
        return $user->school_id !== null && (int) $setting->school_id === (int) $user->school_id;
    }
}
