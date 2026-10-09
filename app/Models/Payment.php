<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Paiement CoriSchool.
 *
 * La confirmation est manuelle aujourd'hui (le Super Admin atteste la
 * réception), mais l'entité distingue déjà revenus « première année » et
 * « renouvellement » via `license.type`. Prête pour les providers futurs.
 */
class Payment extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = [
        'school_id',
        'license_id',
        'amount',
        'currency',
        'status',
        'method',
        'reference',
        'metadata',
        'paid_at',
        'confirmed_at',
        'created_by',
        'confirmed_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'method' => PaymentMethod::class,
            'amount' => 'integer',
            'metadata' => 'array',
            'paid_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function scopeStatus(Builder $query, PaymentStatus|string|null $status): Builder
    {
        if ($status === null || $status === '') {
            return $query;
        }

        return $query->where('status', $status instanceof PaymentStatus ? $status->value : $status);
    }

    /** Revenu issu d'une licence initiale (première année). */
    public function scopeInitial(Builder $query): Builder
    {
        return $query->whereHas('license', fn (Builder $q) => $q->where('type', 'initial'));
    }

    /** Revenu issu d'un renouvellement. */
    public function scopeRenewals(Builder $query): Builder
    {
        return $query->whereHas('license', fn (Builder $q) => $q->where('type', 'renewal'));
    }

    public function isConfirmed(): bool
    {
        return $this->status === PaymentStatus::Confirmed;
    }

    /** Montant affichable, ex. « 150 000 FCFA ». */
    public function formattedAmount(): string
    {
        return number_format($this->amount, 0, ',', ' ').' FCFA';
    }
}