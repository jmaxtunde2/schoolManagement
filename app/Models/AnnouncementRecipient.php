<?php

namespace App\Models;

use App\Enums\NotificationStatus;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * État d'envoi d'une annonce à un parent sur un canal donné.
 */
class AnnouncementRecipient extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'announcement_id', 'parent_id', 'student_id', 'channel', 'phone', 'email',
        'message', 'status', 'provider', 'external_id', 'error', 'attempts',
        'idempotency_key', 'sent_at', 'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => NotificationStatus::class,
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(ParentGuardian::class, 'parent_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** Renseigne le statut et renvoie true si la valeur a réellement changé. */
    public function markStatus(NotificationStatus $status): bool
    {
        $changed = $this->status !== $status;

        $this->status = $status;
        $this->save();

        return $changed;
    }
}
