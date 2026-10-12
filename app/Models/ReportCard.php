<?php

namespace App\Models;

use App\Enums\ReportCardStatus;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ReportCard extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'student_id',
        'class_room_id',
        'academic_year_id',
        'academic_period_id',
        'version',
        'status',
        'general_average',
        'rank',
        'total_students',
        'appreciation',
        'attendance_summary',
        'snapshot',
        'verification_token',
        'generated_at',
        'generated_by',
        'published_at',
        'published_by',
        'pdf_path',
        'pdf_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReportCardStatus::class,
            'general_average' => 'decimal:2',
            'attendance_summary' => 'array',
            'snapshot' => 'array',
            'generated_at' => 'datetime',
            'published_at' => 'datetime',
            'pdf_generated_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class, 'academic_period_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReportCardItem::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function getPdfUrlAttribute(): ?string
    {
        return $this->pdf_path ? Storage::disk('local')->url($this->pdf_path) : null;
    }

    public function hasSettledFees(): bool
    {
        $student = $this->student;

        return $student === null || $student->hasSettledFees((int) $this->academic_year_id);
    }

    /**
     * @return array{configured: bool, settled: bool, amount_due: int, amount_paid: int, remaining: int}
     */
    public function feeStatus(): array
    {
        $contribution = $this->student?->contributionFor((int) $this->academic_year_id);

        if ($contribution === null) {
            return ['configured' => false, 'settled' => true, 'amount_due' => 0, 'amount_paid' => 0, 'remaining' => 0];
        }

        $due = (int) $contribution->amount_due;
        $paid = (int) $contribution->amount_paid;

        return [
            'configured' => true,
            'settled' => $contribution->isPaid(),
            'amount_due' => $due,
            'amount_paid' => $paid,
            'remaining' => max(0, $due - $paid),
        ];
    }
}
