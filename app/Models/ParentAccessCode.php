<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentAccessCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'school_id',
        'academic_year_id',
        'code_hash',
        'code',
        'expires_at',
        'used_at',
        'revoked_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'code' => 'encrypted',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return ! is_null($this->used_at);
    }

    public function isRevoked(): bool
    {
        return ! is_null($this->revoked_at);
    }

    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isUsed() && ! $this->isRevoked();
    }
}
