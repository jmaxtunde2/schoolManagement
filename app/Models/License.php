<?php

namespace App\Models;

use App\Enums\LicenseStatus;
use App\Enums\LicenseType;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Licence CoriSchool : le droit d'utiliser la plateforme pour une période.
 *
 *   initial  → première année (actif + licence)     150 000 FCFA
 *   renewal  → années suivantes                      70 000 FCFA
 *
 * Une licence est un véritable objet métier, jamais un simple champ de School.
 * Liée à une année scolaire (Phase 5) et à son(ses) paiement(s).
 */
class License extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'type',
        'amount',
        'currency',
        'status',
        'starts_at',
        'expires_at',
        'paid_at',
        'payment_reference',
        'notes',
        'created_by',
        'activated_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => LicenseType::class,
            'status' => LicenseStatus::class,
            'amount' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function scopeStatus(Builder $query, LicenseStatus|string|null $status): Builder
    {
        if ($status === null || $status === '') {
            return $query;
        }

        return $query->where('status', $status instanceof LicenseStatus ? $status->value : $status);
    }

    /** Licences actives à la date courante. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', LicenseStatus::Active->value);
    }

    /** Licences expirées à la date courante. */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', LicenseStatus::Expired->value);
    }

    /** Licences expirant dans moins de N jours. */
    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->where('status', LicenseStatus::Active->value)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    public function isActive(): bool
    {
        return $this->status === LicenseStatus::Active;
    }

    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }
        return $this->expires_at->isPast();
    }

    public function isRenewal(): bool
    {
        return $this->type === LicenseType::Renewal;
    }

    /** Nombre de jours restants avant expiration (négatif si dépassée). */
    public function daysRemaining(): ?int
    {
        if ($this->expires_at === null) {
            return null;
        }

        return now()->diffInDays($this->expires_at, false);
    }

    /** Montant affichable, ex. « 150 000 FCFA ». */
    public function formattedAmount(): string
    {
        return number_format($this->amount, 0, ',', ' ').' FCFA';
    }
}