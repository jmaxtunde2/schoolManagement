<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\ClassRoomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Représente une classe (table `classes`, mot réservé PHP -> modèle nommé ClassRoom). */
class ClassRoom extends Model
{
    /** @use HasFactory<ClassRoomFactory> */
    use BelongsToSchool, HasFactory;

    protected $table = 'classes';

    protected $fillable = ['name', 'level', 'capacity'];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'class_subject', 'class_id', 'subject_id')
            ->withPivot('default_coefficient')
            ->withTimestamps();
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherClassSubject::class, 'class_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'class_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /** Niveaux considérés comme primaire (maternelle + élémentaire). */
    public const PRIMARY_LEVELS = ['PS', 'MS', 'GS', 'CI', 'CP', 'CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2'];

    /**
     * Filtre les classes du primaire (maternelle + élémentaire).
     */
    public function scopePrimary($query)
    {
        return $query->whereIn('level', self::PRIMARY_LEVELS);
    }

    /**
     * Filtre les classes du secondaire (6ème à Terminale).
     */
    public function scopeSecondary($query)
    {
        return $query->whereNotIn('level', self::PRIMARY_LEVELS);
    }
}
