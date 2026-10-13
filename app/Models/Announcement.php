<?php

namespace App\Models;

use App\Enums\AnnouncementStatus;
use App\Models\Concerns\BelongsToSchool;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Annonce libre adressée aux parents (réunion, information, convocation…).
 * Rattachée à une école et ciblant un ensemble de parents via une audience.
 */
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use BelongsToSchool, HasFactory;

    public const AUDIENCE_ALL = 'all';

    public const AUDIENCE_CLASSES = 'classes';

    public const AUDIENCE_LEVELS = 'levels';

    public const AUDIENCE_GUARDIANS = 'guardians';

    public const AUDIENCES = [
        self::AUDIENCE_ALL,
        self::AUDIENCE_CLASSES,
        self::AUDIENCE_LEVELS,
        self::AUDIENCE_GUARDIANS,
    ];

    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const CHANNEL_EMAIL = 'email';

    public const CHANNELS = [self::CHANNEL_WHATSAPP, self::CHANNEL_EMAIL];

    protected $fillable = [
        'created_by', 'title', 'body', 'channels', 'audience_type', 'levels',
        'attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size',
        'scheduled_at', 'sent_at', 'status',
    ];

    /** Exposé dans les listes Inertia pour afficher le ciblage en clair. */
    protected $appends = ['audience_label'];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'levels' => 'array',
            'status' => AnnouncementStatus::class,
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'attachment_size' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(ClassRoom::class, 'announcement_class', 'announcement_id', 'class_id')
            ->withTimestamps();
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(ParentGuardian::class, 'announcement_parent', 'announcement_id', 'parent_id')
            ->withTimestamps();
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(AnnouncementRecipient::class);
    }

    public function hasAttachment(): bool
    {
        return ! empty($this->attachment_path);
    }

    /** URL publique de la pièce jointe (utilisée pour l'envoi WhatsApp « lien »). */
    public function attachmentUrl(): ?string
    {
        return $this->attachment_path ? Storage::disk('public')->url($this->attachment_path) : null;
    }

    public function audienceLabel(): string
    {
        return match ($this->audience_type) {
            self::AUDIENCE_CLASSES => 'Classes : '.($this->classes->pluck('name')->join(', ') ?: '—'),
            self::AUDIENCE_LEVELS => 'Niveaux : '.(collect($this->levels)->join(', ') ?: '—'),
            self::AUDIENCE_GUARDIANS => 'Parents sélectionnés ('.($this->guardians_count ?? $this->guardians()->count()).')',
            default => 'Tous les parents',
        };
    }

    public function getAudienceLabelAttribute(): string
    {
        return $this->audienceLabel();
    }

    /** Envoi possible uniquement si l'annonce n'est pas déjà finalisée. */
    public function isDispatchable(): bool
    {
        return in_array($this->status, [
            AnnouncementStatus::Draft,
            AnnouncementStatus::Scheduled,
        ], true);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [
            AnnouncementStatus::Draft,
            AnnouncementStatus::Scheduled,
        ], true);
    }

    public function isDeletable(): bool
    {
        return $this->status !== AnnouncementStatus::Sending;
    }
}
