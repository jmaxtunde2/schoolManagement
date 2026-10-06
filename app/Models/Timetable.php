<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\TimetableFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un cours de l'emploi du temps hebdomadaire d'une école.
 *
 * school_id est porté par BelongsToSchool : le cloisonnement multi-école est donc
 * appliqué automatiquement, y compris sur les resolutions de relation implicites.
 * Les classes, matières, enseignants et années scolaires référencés sont validés
 * côté serveur comme appartenant à la même école (voir TimetableRequest).
 */
class Timetable extends Model
{
    /** @use HasFactory<TimetableFactory> */
    use BelongsToSchool, HasFactory;

    public const DAYS = [
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
        6 => 'Samedi',
    ];

    /** @return array<int, string> */
    public static function days(): array
    {
        return self::DAYS;
    }

    protected $fillable = [
        'academic_year_id',
        'class_id',
        'subject_id',
        'teacher_id',
        'day_of_week',
        'starts_at',
        'ends_at',
        'room',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'starts_at' => 'datetime:H:i',
            'ends_at' => 'datetime:H:i',
        ];
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

    public function getDayLabelAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? '—';
    }

    /** « 08:00 – 09:55 » */
    public function getTimeRangeAttribute(): string
    {
        return $this->formatTime($this->starts_at).' – '.$this->formatTime($this->ends_at);
    }

    public function formatTime(mixed $value): string
    {
        if (! $value) {
            return '—';
        }

        return $value instanceof \DateTimeInterface
            ? $value->format('H:i')
            : substr((string) $value, 0, 5);
    }
}
