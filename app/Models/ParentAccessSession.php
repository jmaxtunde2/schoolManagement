<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentAccessSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'school_id',
        'academic_year_id',
        'token_hash',
        'device_name',
        'ip_address',
        'user_agent',
        'expires_at',
        'last_activity_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentGuardian::class, 'parent_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return ! is_null($this->revoked_at);
    }

    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isRevoked();
    }
}
