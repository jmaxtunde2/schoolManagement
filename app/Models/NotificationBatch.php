<?php

namespace App\Models;

use App\Enums\NotificationStatus;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Notification consolidée d'un élève pour une journée donnée.
 * Trace les évaluations regroupées et les destinataires (parents).
 */
class NotificationBatch extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'notification_type',
        'notification_date',
        'student_id',
        'channel',
        'status',
        'recipient_count',
        'message',
        'cost',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'notification_date' => 'date',
            'status' => NotificationStatus::class,
            'recipient_count' => 'integer',
            'cost' => 'decimal:6',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function evaluations(): BelongsToMany
    {
        return $this->belongsToMany(Evaluation::class, 'notification_batch_evaluation')
            ->withTimestamps();
    }

    /** Destinataires externes (lignes de la table notifications). */
    public function recipients(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
