<?php

namespace App\Models;

use Database\Factories\PlatformSubjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformSubject extends Model
{
    /** @use HasFactory<PlatformSubjectFactory> */
    use HasFactory;

    protected $fillable = ['name', 'code', 'color', 'position', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function examResources(): HasMany
    {
        return $this->hasMany(ExamResource::class, 'platform_subject_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('position')->orderBy('name');
    }
}
