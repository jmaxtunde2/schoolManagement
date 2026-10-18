<?php

namespace App\Models;

use App\Enums\ExamDifficulty;
use App\Enums\ExamResourceStatus;
use App\Enums\ExamResourceType;
use Database\Factories\ExamResourceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Ressource pédagogique de la banque d'épreuves.
 *
 * Une ressource sans `school_id` appartient à la plateforme CoriSchool
 * (banque centrale) et ne peut être modifiée par aucun établissement.
 * Une ressource avec `school_id` appartient à un établissement.
 *
 * Le modèle n'utilise volontairement pas BelongsToSchool : le scope
 * d'établissement masquerait les ressources plateforme (school_id nul).
 */
class ExamResource extends Model
{
    /** @use HasFactory<ExamResourceFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'platform_level_id',
        'platform_subject_id',
        'created_by',
        'title',
        'description',
        'type',
        'difficulty',
        'duration_minutes',
        'academic_year',
        'period',
        'content',
        'is_free',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ExamResourceType::class,
            'difficulty' => ExamDifficulty::class,
            'status' => ExamResourceStatus::class,
            'is_free' => 'boolean',
            'duration_minutes' => 'integer',
            'published_at' => 'datetime',
            'downloads_count' => 'integer',
            'views_count' => 'integer',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(PlatformLevel::class, 'platform_level_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(PlatformSubject::class, 'platform_subject_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ExamResourceAttachment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ExamResourceAttachment::class)
            ->where('kind', ExamResourceAttachment::KIND_DOCUMENT);
    }

    public function answerKeys(): HasMany
    {
        return $this->hasMany(ExamResourceAttachment::class)
            ->where('kind', ExamResourceAttachment::KIND_ANSWER_KEY);
    }

    public function isPlatformResource(): bool
    {
        return $this->school_id === null;
    }

    public function isPublished(): bool
    {
        return $this->status === ExamResourceStatus::Published;
    }

    public function hasAnswerKey(): bool
    {
        return $this->answerKeys()->exists();
    }

    /**
     * Ressources visibles par un utilisateur d'établissement : les ressources
     * plateforme publiées et les ressources de son propre établissement.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isPlatformAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where(function (Builder $platform) {
                $platform->whereNull('school_id')
                    ->where('status', ExamResourceStatus::Published->value);
            })->orWhere('school_id', $user->school_id);
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ExamResourceStatus::Published->value);
    }
}
