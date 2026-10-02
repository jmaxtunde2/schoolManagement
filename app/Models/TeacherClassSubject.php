<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot enrichi teacher/class/subject. Pas de school_id propre : l'isolation est déjà
 * garantie transitivement par teacher_id (le professeur appartient lui-même à une école).
 */
class TeacherClassSubject extends Model
{
    protected $table = 'teacher_class_subject';

    protected $fillable = ['teacher_id', 'class_id', 'subject_id'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
