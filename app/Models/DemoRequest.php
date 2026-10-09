<?php

namespace App\Models;

use App\Enums\DemoRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Demande de démonstration déposée depuis la page publique.
 *
 * Cette entité est volontairement HORS tenant : à ce stade aucune école
 * n'existe encore (aucun school_id), c'est la matière première du parcours
 * commercial Coriyase. Elle ne doit donc jamais utiliser le trait
 * BelongsToSchool, qui la rattacherait à tort à une école.
 */
class DemoRequest extends Model
{
    protected $fillable = [
        'school_name',
        'address',
        'phone',
        'email',
        'preferred_demo_date',
        'preferred_demo_time',
        'status',
        'contacted_at',
        'scheduled_at',
        'demo_done_at',
        'approved_at',
        'rejected_at',
        'cancelled_at',
        'handled_by',
        'internal_notes',
        'school_id',
        'payment_requested_at',
        'payment_confirmed_at',
        'payment_confirmed_by',
        'activated_at',
        'activated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DemoRequestStatus::class,
            'preferred_demo_date' => 'date',
            'contacted_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'demo_done_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'payment_requested_at' => 'datetime',
            'payment_confirmed_at' => 'datetime',
            'activated_at' => 'datetime',
        ];
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /** École éventuellement issue de cette demande (après activation). */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function scopeStatus(Builder $query, DemoRequestStatus|string|null $status): Builder
    {
        if ($status === null || $status === '') {
            return $query;
        }

        return $query->where('status', $status instanceof DemoRequestStatus ? $status->value : $status);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($term) {
            $builder->where('school_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        });
    }

    /** Le créneau souhaité, formaté pour l'affichage (16/10/2026 à 14:30). */
    public function preferredSlot(): string
    {
        $time = substr((string) $this->preferred_demo_time, 0, 5);

        return $this->preferred_demo_date?->format('d/m/Y').' à '.$time;
    }

    /** Montant du paiement initial (première année) en FCFA. */
    public function initialAmount(): int
    {
        return (int) config('cori.billing.initial_amount');
    }

    public function isPaymentRequested(): bool
    {
        return $this->payment_requested_at !== null;
    }

    public function isPaymentConfirmed(): bool
    {
        return $this->payment_confirmed_at !== null;
    }

    /** La demande est doublement approuvée : démo OK + paiement initial confirmé. */
    public function canBeActivated(): bool
    {
        return $this->status === DemoRequestStatus::Approved
            && $this->isPaymentConfirmed()
            && $this->activated_at === null;
    }

    public function isActivated(): bool
    {
        return $this->activated_at !== null && $this->school_id !== null;
    }
}
