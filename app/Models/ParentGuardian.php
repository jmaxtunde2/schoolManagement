<?php

namespace App\Models;

use Database\Factories\ParentGuardianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Parent ou tuteur d'un ou plusieurs élèves (table `parents`). Ne se connecte pas en V1. */
class ParentGuardian extends Model
{
    /** @use HasFactory<ParentGuardianFactory> */
    use HasFactory, BelongsToSchool;

    protected $table = 'parents';

    protected $fillable = ['name', 'phone', 'email', 'address', 'photo_path', 'is_active', 'user_id'];

    protected function casts(): array { return ['is_active' => 'boolean']; }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(User::class); }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'parent_student', 'parent_id', 'student_id')
            ->withPivot('relationship')
            ->withTimestamps();
    }
}
