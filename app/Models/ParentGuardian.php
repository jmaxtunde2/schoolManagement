<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\ParentGuardianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

/** Parent ou tuteur d'un ou plusieurs élèves (table `parents`). Ne se connecte pas en V1. */
class ParentGuardian extends Model
{
    /** @use HasFactory<ParentGuardianFactory> */
    use BelongsToSchool, HasFactory;

    protected $table = 'parents';

    /**
     * `phone` reste le numéro principal ; `phone_secondary` est le second numéro
     * du responsable. Aucune bascule automatique vers le secondaire n'est appliquée :
     * le module SMS existant continue de cibler le numéro principal.
     */
    protected $fillable = ['name', 'phone', 'phone_secondary', 'email', 'address', 'photo_path', 'is_active', 'user_id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'parent_student', 'parent_id', 'student_id')
            ->withPivot('relationship')
            ->withTimestamps();
    }
}
