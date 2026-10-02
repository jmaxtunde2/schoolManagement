<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationResult extends Model
{
    use BelongsToSchool;

    protected $fillable = ['evaluation_id', 'student_id', 'score', 'is_absent', 'updated_by'];

    protected function casts(): array
    {
        return ['score' => 'decimal:2', 'is_absent' => 'boolean'];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
