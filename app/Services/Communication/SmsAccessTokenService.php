<?php

namespace App\Services\Communication;

use App\Models\Evaluation;
use App\Models\Notification;
use App\Models\SmsNotificationAccessToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Génère et valide les accès SMS temporaires aux évaluations.
 *
 * Le token en clair n'est jamais persisté : seul son hash SHA-256 est stocké.
 * Il est associé côté serveur au parent, à l'élève, à l'école et à
 * l'évaluation (ou au lot consolidé). Aucun identifiant de l'URL n'est utilisé
 * pour déterminer à qui appartiennent les données.
 */
class SmsAccessTokenService
{
    /**
     * @return array{token: string, record: SmsNotificationAccessToken, url: string}
     */
    public function issueForNotification(Notification $notification, ?Evaluation $evaluation = null): array
    {
        $plainToken = Str::random($this->tokenLength());
        $expiresAt = now()->addHours($this->ttlHours());
        $evaluationId = $evaluation?->id ?? $notification->evaluation_id;

        $record = SmsNotificationAccessToken::withoutGlobalScopes()->create([
            'token_hash' => $this->hash($plainToken),
            'school_id' => $notification->school_id,
            'parent_id' => $notification->parent_id,
            'student_id' => $notification->student_id,
            'evaluation_id' => $evaluationId,
            'notification_id' => $notification->id,
            'notification_batch_id' => $notification->notification_batch_id,
            'expires_at' => $expiresAt,
            'max_uses' => $this->maxUses(),
            'metadata' => [
                'channel' => 'sms',
                'notification_type' => $notification->notification_type,
            ],
        ]);

        Log::info('sms.access_token.generated', [
            'access_token_id' => $record->id,
            'notification_id' => $notification->id,
            'school_id' => $notification->school_id,
            'parent_id' => $notification->parent_id,
            'student_id' => $notification->student_id,
            'evaluation_id' => $evaluationId,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return [
            'token' => $plainToken,
            'record' => $record,
            'url' => $this->urlFor($plainToken),
        ];
    }

    public function findByPlainToken(string $plainToken): ?SmsNotificationAccessToken
    {
        if ($plainToken === '') {
            return null;
        }

        return SmsNotificationAccessToken::withoutGlobalScopes()
            ->where('token_hash', $this->hash($plainToken))
            ->first();
    }

    /**
     * Consomme une utilisation du token. Le verrou évite qu'un même lien
     * dépasse sa limite en cas de clics simultanés (double-tap mobile, prefetch).
     */
    public function consume(SmsNotificationAccessToken $record, Request $request): bool
    {
        return DB::transaction(function () use ($record, $request) {
            $locked = SmsNotificationAccessToken::withoutGlobalScopes()
                ->lockForUpdate()
                ->find($record->id);

            if (! $locked || ! $locked->isUsable()) {
                Log::warning('sms.access_token.denied', [
                    'access_token_id' => $record->id,
                    'reason' => ! $locked ? 'missing' : $this->unusableReason($locked),
                ]);

                return false;
            }

            $now = now();

            $locked->forceFill([
                'use_count' => $locked->use_count + 1,
                'used_at' => $locked->used_at ?? $now,
                'last_used_at' => $now,
                'last_ip' => $request->ip(),
                'last_user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            ])->save();

            Log::info('sms.access_token.used', [
                'access_token_id' => $locked->id,
                'school_id' => $locked->school_id,
                'parent_id' => $locked->parent_id,
                'student_id' => $locked->student_id,
                'evaluation_id' => $locked->evaluation_id,
                'use_count' => $locked->use_count,
                'ip' => $request->ip(),
            ]);

            return true;
        });
    }

    public function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    public function urlFor(string $plainToken): string
    {
        $baseUrl = rtrim((string) config(
            'communication.sms_access.url',
            config('app.url')
        ), '/');

        return $baseUrl.'/sms/access/'.$plainToken;
    }

    public function messageFor(string $plainToken): string
    {
        return $this->messageForUrl($this->urlFor($plainToken));
    }

    public function messageForUrl(string $url): string
    {
        return trim((string) config(
            'communication.sms_access.message',
            'CoriSchool : une nouvelle évaluation de votre enfant est disponible. Voir les notes :'
        )).' '.$url;
    }

    public function ttlHours(): int
    {
        return max(1, (int) config('communication.sms_access.ttl_hours', 72));
    }

    public function tokenLength(): int
    {
        return max(20, (int) config('communication.sms_access.token_length', 32));
    }

    public function maxUses(): int
    {
        return max(1, (int) config('communication.sms_access.max_uses', 25));
    }

    private function unusableReason(SmsNotificationAccessToken $record): string
    {
        if ($record->isRevoked()) {
            return 'revoked';
        }

        if ($record->isExpired()) {
            return 'expired';
        }

        if ($record->hasReachedUsageLimit()) {
            return 'usage_limit_reached';
        }

        return 'unusable';
    }
}
