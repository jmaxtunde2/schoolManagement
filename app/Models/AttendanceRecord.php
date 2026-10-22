<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceValidationStatus;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    use BelongsToSchool;

    protected $appends = ['recorded_at_label'];

    protected $fillable = [
        'student_id',
        'class_room_id',
        'academic_period_id',
        'attendance_date',
        'status',
        'validation_status',
        'delay_minutes',
        'reason',
        'note',
        'recorded_by',
        'recorded_at',
        'justified_at',
        'justified_by',
        'validated_at',
        'validated_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date:Y-m-d',
            'status' => AttendanceStatus::class,
            'validation_status' => AttendanceValidationStatus::class,
            'recorded_at' => 'datetime',
            'justified_at' => 'datetime',
            'validated_at' => 'datetime',
        ];
    }

    protected function recordedAtLabel(): Attribute
    {
        return Attribute::get(function () {
            $at = $this->recorded_at ?? $this->created_at;

            if (! $at) {
                return null;
            }

            $days = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
            $months = [
                1 => 'jan', 'fév', 'mar', 'avr', 'mai', 'juin',
                'juil', 'août', 'sep', 'oct', 'nov', 'déc',
            ];

            return sprintf(
                '%s %d %s %d %s',
                $days[(int) $at->dayOfWeek],
                (int) $at->day,
                $months[(int) $at->month],
                (int) $at->year,
                $at->format('H:i:s'),
            );
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class, 'academic_period_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function justifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'justified_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function justifications(): HasMany
    {
        return $this->hasMany(AttendanceJustification::class);
    }
}
