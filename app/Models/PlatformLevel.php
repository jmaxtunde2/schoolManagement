<?php

namespace App\Models;

use Database\Factories\PlatformLevelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformLevel extends Model
{
    /** @use HasFactory<PlatformLevelFactory> */
    use HasFactory;

    protected $fillable = ['name', 'code', 'position', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function examResources(): HasMany
    {
        return $this->hasMany(ExamResource::class, 'platform_level_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('position')->orderBy('name');
    }
}
