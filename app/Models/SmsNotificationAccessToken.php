<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Accès SMS temporaire et contextualisé à une évaluation.
 *
 * Le token brut n'existe qu'au moment de la génération puis dans le SMS.
 * Seul son hash SHA-256 est conservé en base. Un token est toujours rattaché
 * au parent, à l'élève, à l'école et à l'évaluation (ou au lot de notifications)
 * concernés : le serveur ne dépend jamais d'un identifiant fourni dans l'URL.
 */
class SmsNotificationAccessToken extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'token_hash',
        'school_id',
        'parent_id',
        'student_id',
        'evaluation_id',
        'notification_id',
        'notification_batch_id',
        'expires_at',
        'used_at',
        'last_used_at',
        'use_count',
        'max_uses',
        'revoked_at',
        'revoked_reason',
        'last_ip',
        'last_user_agent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
            'use_count' => 'integer',
            'max_uses' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentGuardian::class, 'parent_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(NotificationBatch::class, 'notification_batch_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function hasReachedUsageLimit(): bool
    {
        return $this->max_uses > 0 && $this->use_count >= $this->max_uses;
    }

    public function isUsable(): bool
    {
        return ! $this->isExpired()
            && ! $this->isRevoked()
            && ! $this->hasReachedUsageLimit();
    }

    public function revoke(?string $reason = null): void
    {
        $this->forceFill([
            'revoked_at' => now(),
            'revoked_reason' => $reason,
        ])->save();
    }
}
