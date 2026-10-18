<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RuntimeException;

class AuditLog extends Model
{
    protected $fillable = ['school_id', 'user_id', 'action', 'auditable_type', 'auditable_id', 'metadata', 'ip_address', 'user_agent'];

    protected $casts = ['metadata' => 'array'];

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new RuntimeException("Les journaux d'activité sont immuables et ne peuvent pas être modifiés.");
        });

        static::deleting(function (): void {
            throw new RuntimeException("Les journaux d'activité sont immuables et ne peuvent pas être supprimés.");
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
