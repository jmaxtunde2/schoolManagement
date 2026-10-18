<?php

namespace App\Models;

use Database\Factories\ExamResourceAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ExamResourceAttachment extends Model
{
    /** @use HasFactory<ExamResourceAttachmentFactory> */
    use HasFactory;

    public const KIND_DOCUMENT = 'document';

    public const KIND_ANSWER_KEY = 'answer_key';

    protected $fillable = [
        'exam_resource_id',
        'kind',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'watermarked',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'watermarked' => 'boolean',
        ];
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(ExamResource::class, 'exam_resource_id');
    }

    public function isAnswerKey(): bool
    {
        return $this->kind === self::KIND_ANSWER_KEY;
    }

    public function isImage(): bool
    {
        return in_array($this->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true);
    }

    public function exists(): bool
    {
        return $this->path && Storage::disk($this->disk)->exists($this->path);
    }
}
