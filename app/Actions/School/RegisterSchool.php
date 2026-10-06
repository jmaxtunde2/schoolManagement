<?php

namespace App\Actions\School;

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Support\SchoolBranding;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Inscription d'un nouvel établissement.
 *
 * Crée dans une seule transaction : l'école, ses réglages, son année scolaire
 * courante et le compte administrateur. Tout reste dans la même base, l'isolation
 * reposant uniquement sur school_id — aucun schéma ni base par école.
 */
class RegisterSchool
{
    /**
     * @param  array<string, mixed>  $data  Colonnes de school_settings validées par RegisterSchoolRequest.
     * @param  array<string, mixed>  $admin  Colonnes du compte administrateur (name, email, password).
     * @return array{school: School, setting: SchoolSetting, admin: User, academic_year: AcademicYear}
     */
    public function handle(array $data, array $admin, ?UploadedFile $logo = null): array
    {
        return DB::transaction(function () use ($data, $admin, $logo) {
            $school = School::create([
                'name' => $data['school_name'],
                'is_active' => true,
            ]);

            $attributes = Arr::except($data, ['logo']);

            if ($logo) {
                $attributes['logo_path'] = $logo->store("schools/{$school->id}/logo", 'public');
            }

            // `school_settings` porte school_id et non l'inverse : on l'écrit explicitement
            // car aucun utilisateur n'est encore connecté (le scope global ne peut rien déduire).
            $setting = SchoolSetting::withoutGlobalScopes()->create([
                ...$attributes,
                'school_id' => $school->id,
            ]);

            // Le compte administrateur est créé sans utilisateur connecté :
            // ni `name`/`email`/`password` ni `school_id` ne sont fillable sur User,
            // on écrit donc directement les attributs.
            $adminUser = new User;
            $adminUser->forceFill([
                'school_id' => $school->id,
                'name' => $admin['name'],
                'email' => strtolower($admin['email']),
                'password' => Hash::make($admin['password']),
                'phone' => $admin['phone'] ?? null,
                'role' => Role::Admin,
                'is_active' => true,
                'email_verified_at' => now(),
            ])->save();

            $academicYear = AcademicYear::withoutGlobalScopes()->create([
                'school_id' => $school->id,
                'name' => $data['academic_year_name'],
                'starts_on' => $data['academic_year_starts_on'],
                'ends_on' => $data['academic_year_ends_on'],
                'is_current' => true,
            ]);

            Cache::forget(SchoolBranding::cacheKey($school->id));

            return [
                'school' => $school->fresh(),
                'setting' => $setting,
                'admin' => $adminUser,
                'academic_year' => $academicYear,
            ];
        });
    }
}
