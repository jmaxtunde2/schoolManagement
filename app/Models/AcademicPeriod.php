<?php

namespace App\Models;

use Database\Factories\AcademicPeriodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicPeriod extends Model
{
    /** @use HasFactory<AcademicPeriodFactory> */
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'academic_year_id',
        'name',
        'position',
        'starts_at',
        'ends_at',
        'is_closed',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date:Y-m-d',
            'ends_at' => 'date:Y-m-d',
            'is_closed' => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(ReportCard::class);
    }
}
