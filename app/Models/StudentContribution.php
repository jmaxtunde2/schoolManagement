<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentContribution extends Model
{
    use BelongsToSchool;

    protected $fillable = ['student_id', 'parent_id', 'academic_year_id', 'amount_due', 'amount_paid', 'status', 'paid_at'];

    protected $casts = ['paid_at' => 'datetime'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentGuardian::class, 'parent_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid' || $this->amount_paid >= $this->amount_due;
    }
}
