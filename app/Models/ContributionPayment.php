<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContributionPayment extends Model
{
    use BelongsToSchool;

    /** Libellés lisibles des moyens de paiement. */
    public const METHODS = [
        'cash' => 'Espèces',
        'mobile_money' => 'Mobile Money',
        'bank_transfer' => 'Virement bancaire',
        'check' => 'Chèque',
        'other' => 'Autre',
    ];

    protected $fillable = [
        'student_contribution_id',
        'parent_id',
        'amount',
        'method',
        'reference',
        'receipt_number',
        'paid_at',
        'recorded_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'date:Y-m-d',
        ];
    }

    public function contribution(): BelongsTo
    {
        return $this->belongsTo(StudentContribution::class, 'student_contribution_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentGuardian::class, 'parent_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? ucfirst((string) $this->method);
    }
}
