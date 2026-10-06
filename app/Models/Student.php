<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use BelongsToSchool, HasFactory;

    protected $fillable = ['class_id', 'matricule', 'first_name', 'last_name', 'birth_date', 'gender', 'photo_path', 'is_active'];

    /** La photo est exposée en URL dans toutes les listes Inertia (élèves, présences, portail parent). */
    protected $appends = ['photo_url'];

    protected function casts(): array
    {
        return ['birth_date' => 'date:Y-m-d', 'is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        // La photo vit sur le disque `public` : on ne laisse pas d'orphelin
        // quand l'élève est supprimé.
        static::deleted(function (Student $student) {
            if ($student->photo_path) {
                Storage::disk('public')->delete($student->photo_path);
            }
        });
    }

    /**
     * URL publique de la photo, alignée sur User::photoUrl().
     */
    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(ParentGuardian::class, 'parent_student', 'student_id', 'parent_id')
            ->withPivot('relationship')
            ->withTimestamps();
    }

    public function results(): HasMany
    {
        return $this->hasMany(EvaluationResult::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /** URL publique de la photo, ou null si l'élève n'en a pas. Jamais de Base64 en base. */
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
