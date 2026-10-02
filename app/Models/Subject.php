<?php

namespace App\Models;

use Database\Factories\SubjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    /** @use HasFactory<SubjectFactory> */
    use HasFactory, BelongsToSchool;

    protected $fillable = ['name', 'code'];

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(ClassRoom::class, 'class_subject', 'subject_id', 'class_id')
            ->withPivot('default_coefficient')
            ->withTimestamps();
    }
}
