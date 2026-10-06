<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCardItem extends Model
{
    protected $fillable = [
        'report_card_id',
        'subject_id',
        'subject_name',
        'coefficient',
        'average',
        'evaluation_count',
        'rank',
        'appreciation',
        'teacher_comment',
    ];

    protected function casts(): array
    {
        return [
            'average' => 'decimal:2',
            'coefficient' => 'decimal:1',
            'evaluation_count' => 'integer',
        ];
    }

    public function reportCard(): BelongsTo
    {
        return $this->belongsTo(ReportCard::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
