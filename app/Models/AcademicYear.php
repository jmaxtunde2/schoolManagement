<?php

namespace App\Models;

use Database\Factories\AcademicYearFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    /** @use HasFactory<AcademicYearFactory> */
    use HasFactory, BelongsToSchool;

    protected $fillable = ['name', 'starts_on', 'ends_on', 'is_current'];

    protected function casts(): array
    {
        return ['starts_on' => 'date:Y-m-d', 'ends_on' => 'date:Y-m-d', 'is_current' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(function (AcademicYear $year) {
            if ($year->is_current) {
                static::where('school_id', $year->school_id)->where('id', '!=', $year->id)->update(['is_current' => false]);
            }
        });
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(AcademicPeriod::class)->orderBy('position');
    }
}
