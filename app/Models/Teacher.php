<?php

namespace App\Models;

use Database\Factories\TeacherFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    /** @use HasFactory<TeacherFactory> */
    use HasFactory, BelongsToSchool;

    protected $fillable = ['user_id', 'phone'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TeacherClassSubject::class);
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(ClassRoom::class, 'teacher_class_subject', 'teacher_id', 'class_id')->distinct();
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'teacher_class_subject', 'teacher_id', 'subject_id')->distinct();
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    /** Vérifie qu'un enseignant est bien habilité à évaluer cette classe sur cette matière. */
    public function isAssignedTo(int $classId, int $subjectId): bool
    {
        return $this->assignments()->where('class_id', $classId)->where('subject_id', $subjectId)->exists();
    }
}
