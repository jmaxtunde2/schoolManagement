<?php

namespace App\Models;

use App\Enums\NotificationStatus;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    use BelongsToSchool;

    protected $table = 'notifications';

    protected $fillable = [
        'evaluation_id', 'student_id', 'parent_id', 'channel', 'phone', 'email', 'message', 'provider',
        'status', 'idempotency_key', 'external_id', 'error', 'attempts', 'estimated_cost', 'cost_currency', 'sent_at', 'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => NotificationStatus::class,
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(ParentGuardian::class, 'parent_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function recordStatus(NotificationStatus $status, ?string $message = null): void
    {
        $this->status = $status;
        $this->save();

        $this->logs()->create([
            'school_id' => $this->school_id,
            'status' => $status->value,
            'message' => $message,
        ]);
    }
}
