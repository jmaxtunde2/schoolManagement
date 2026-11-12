<?php

namespace App\Services\ParentAccess;

use App\Models\AcademicYear;
use App\Models\ParentAccessSession;
use App\Models\ParentGuardian;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ParentAccessSessionService
{
    public function createSession(ParentGuardian $parent, School $school, ?AcademicYear $academicYear, Request $request): array
    {
        // Determine session expiration based on school year end
        $expiresAt = null;
        if ($academicYear && $academicYear->ends_on) {
            // Expire at end of school year (23:59)
            $expiresAt = $academicYear->ends_on->copy()->endOfDay();
        } else {
            // Fallback if no academic year info - but requirement says use school year end
            $expiresAt = now()->addMonths(12);
        }

        $plainToken = Str::random(64);
        $tokenHash = hash('sha256', $plainToken);

        // Un seul appareil actif par défaut : toute nouvelle connexion révoque
        // les sessions existantes du parent pour cet établissement.
        $maxDevices = (int) config('corischool.parent_access.max_devices', 1);
        if ($maxDevices > 0) {
            $activeSessions = ParentAccessSession::query()
                ->where('parent_id', $parent->id)
                ->where('school_id', $school->id)
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->count();

            if ($activeSessions >= $maxDevices) {
                $excess = $activeSessions - $maxDevices + 1;

                $oldestIds = ParentAccessSession::query()
                    ->where('parent_id', $parent->id)
                    ->where('school_id', $school->id)
                    ->whereNull('revoked_at')
                    ->where('expires_at', '>', now())
                    ->orderBy('last_activity_at')
                    ->orderBy('created_at')
                    ->limit($excess)
                    ->pluck('id');

                if ($oldestIds->isNotEmpty()) {
                    ParentAccessSession::query()
                        ->whereIn('id', $oldestIds)
                        ->update(['revoked_at' => now()]);
                }
            }
        }

        $session = ParentAccessSession::create([
            'parent_id' => $parent->id,
            'school_id' => $school->id,
            'academic_year_id' => $academicYear?->id,
            'token_hash' => $tokenHash,
            'device_name' => $request->header('User-Agent'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'expires_at' => $expiresAt,
            'last_activity_at' => now(),
        ]);

        return [
            'session' => $session,
            'plain_token' => $plainToken,
        ];
    }

    public function findByPlainToken(string $plainToken): ?ParentAccessSession
    {
        $hash = hash('sha256', $plainToken);

        return ParentAccessSession::query()
            ->with(['parent', 'school'])
            ->where('token_hash', $hash)
            ->first();
    }

    /**
     * Session encore exploitable : ni expirée, ni révoquée, avec un parent
     * actif. Le jeton en clair n'est jamais stocké en base.
     */
    public function findValidByPlainToken(?string $plainToken): ?ParentAccessSession
    {
        if (! $plainToken) {
            return null;
        }

        $session = $this->findByPlainToken($plainToken);

        if (! $session || ! $session->isValid()) {
            return null;
        }

        $parent = $session->parent;

        if (! $parent || ! $parent->is_active) {
            return null;
        }

        return $session;
    }

    /**
     * Session valide rattachée à un établissement précis. Point de contrôle
     * utilisé par le mini-site et le bootstrap PWA pour ne jamais exposer le
     * contexte d'une autre école.
     */
    public function findValidForSchool(?string $plainToken, int $schoolId): ?ParentAccessSession
    {
        $session = $this->findValidByPlainToken($plainToken);

        if (! $session || (int) $session->school_id !== $schoolId) {
            return null;
        }

        return $session;
    }
}
