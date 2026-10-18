<?php

namespace App\Models;

use App\Enums\PreinscriptionStatus;
use App\Models\Concerns\BelongsToSchool;
use Database\Factories\PreinscriptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Demande de préinscription déposée depuis le mini-site (phase 7).
 */
class Preinscription extends Model
{
    /** @use HasFactory<PreinscriptionFactory> */
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'birth_date',
        'gender',
        'requested_level',
        'parent_name',
        'parent_phone',
        'parent_email',
        'parent_relationship',
        'notes',
        'documents',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'student_id',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date:Y-m-d',
            'documents' => 'array',
            'status' => PreinscriptionStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /** @return array<int, array{name: string, url: string}> */
    public function documentLinks(): array
    {
        return collect($this->documents ?? [])
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->map(fn (string $path) => [
                'name' => basename($path),
                'url' => Storage::disk('public')->url($path),
            ])
            ->values()
            ->all();
    }
}
