<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\PushSubscriptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Abonnement Web Push d'un utilisateur (un par navigateur/appareil).
 *
 * L'endpoint est une URL unique fournie par le service de push du navigateur.
 * Les clés `public_key` (p256dh) et `auth_token` permettent de chiffrer la
 * charge utile (voir App\Services\WebPush). Un abonnement expiré (410/404)
 * est supprimé automatiquement lors d'un envoi.
 */
class PushSubscription extends Model
{
    /** @use HasFactory<PushSubscriptionFactory> */
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'user_id',
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
        'domain',
        'user_agent',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
