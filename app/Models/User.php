<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',

            // Rôle et statut
            'role' => Role::class,
            'is_active' => 'boolean',

            // Google authentication
            'google_verified_at' => 'datetime',
            'google_reauthenticated_at' => 'datetime',

            // TOTP / Authenticator
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_enabled_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_last_used_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function parentGuardian(): HasOne
    {
        return $this->hasOne(
            ParentGuardian::class,
            'user_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rôles
    |--------------------------------------------------------------------------
    */

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isTeacher(): bool
    {
        return $this->role === Role::Teacher;
    }

    public function isCenseur(): bool
    {
        return $this->role === Role::Censeur;
    }

    public function isSecretary(): bool
    {
        return $this->role === Role::Secretary;
    }

    public function isPlatformAdmin(): bool
    {
        return $this->role === Role::PlatformAdmin;
    }

    public function isParent(): bool
    {
        return $this->role === Role::Parent;
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function recordedAttendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'recorded_by');
    }

    public function justifiedAttendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'justified_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Google re-authentication
    |--------------------------------------------------------------------------
    */

    public function hasRecentGoogleReauthentication(
        int $seconds = 300
    ): bool {
        return $this->google_reauthenticated_at
            ?->greaterThan(now()->subSeconds($seconds))
            ?? false;
    }
}