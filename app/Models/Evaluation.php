<?php

namespace App\Models;

use App\Enums\EvaluationStatus;
use App\Enums\EvaluationType;
use App\Models\Concerns\BelongsToSchool;
use Database\Factories\EvaluationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    /** @use HasFactory<EvaluationFactory> */
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'academic_year_id', 'academic_period_id', 'class_id', 'subject_id', 'teacher_id',
        'title', 'type', 'evaluation_date', 'max_score', 'coefficient',
        'status', 'validated_at', 'validated_by', 'created_by', 'entered_by', 'submitted_by', 'submitted_at',
        'returned_at', 'returned_by', 'return_reason',
    ];

    protected function casts(): array
    {
        return [
            'evaluation_date' => 'date:Y-m-d',
            'max_score' => 'decimal:2',
            'coefficient' => 'decimal:1',
            'type' => EvaluationType::class,
            'status' => EvaluationStatus::class,
            'validated_at' => 'datetime',
            'submitted_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function results(): HasMany
    {
        return $this->hasMany(EvaluationResult::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function isValidated(): bool
    {
        return $this->status === EvaluationStatus::Validated;
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function gradedCount(): int
    {
        return $this->results()->where(fn ($q) => $q->whereNotNull('score')->orWhere('is_absent', true))->count();
    }
}
