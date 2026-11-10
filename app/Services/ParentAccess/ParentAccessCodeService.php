<?php

namespace App\Services\ParentAccess;

use App\Models\AcademicYear;
use App\Models\ParentAccessCode;
use App\Models\ParentGuardian;
use App\Models\School;
use Illuminate\Support\Str;

class ParentAccessCodeService
{
    public function generateCode(ParentGuardian $parent, School $school, ?AcademicYear $academicYear = null, ?int $createdBy = null): array
    {
        // Invalidate all active codes for this parent for this school
        ParentAccessCode::query()
            ->where('parent_id', $parent->id)
            ->where('school_id', $school->id)
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->update(['revoked_at' => now()]);

        // Generate a unique code
        $plainCode = $this->generateUniqueCode();
        $codeHash = hash('sha256', $plainCode);

        $validityMinutes = max(1, (int) config('corischool.parent_access.code_validity_minutes', 1440));
        $expiresAt = now()->addMinutes($validityMinutes);

        $code = ParentAccessCode::create([
            'parent_id' => $parent->id,
            'school_id' => $school->id,
            'academic_year_id' => $academicYear?->id,
            'code_hash' => $codeHash,
            'code' => $plainCode,
            'expires_at' => $expiresAt,
            'created_by' => $createdBy,
        ]);

        return [
            'code' => $code,
            'plain_code' => $plainCode,
        ];
    }

    private function generateUniqueCode(): string
    {
        do {
            // Format: CS-XXXXXXX
            $code = 'CS-'.strtoupper(Str::random(8));
            $hash = hash('sha256', $code);
            $exists = ParentAccessCode::query()->where('code_hash', $hash)->exists();
        } while ($exists);

        return $code;
    }

    public function findByPlainCode(string $plainCode): ?ParentAccessCode
    {
        $hash = hash('sha256', $plainCode);

        return ParentAccessCode::query()->where('code_hash', $hash)->first();
    }
}
