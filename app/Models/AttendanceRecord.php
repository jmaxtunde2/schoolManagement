<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
	use BelongsToSchool;

	protected $fillable = [
		'student_id',
		'class_room_id',
		'academic_period_id',
		'attendance_date',
		'status',
		'delay_minutes',
		'reason',
		'note',
		'recorded_by',
		'justified_at',
		'justified_by',
	];

	protected function casts(): array
	{
		return [
			'attendance_date' => 'date:Y-m-d',
			'status' => AttendanceStatus::class,
			'justified_at' => 'datetime',
		];
	}

	public function student(): BelongsTo
	{
		return $this->belongsTo(Student::class);
	}

	public function classRoom(): BelongsTo
	{
		return $this->belongsTo(ClassRoom::class);
	}

	public function period(): BelongsTo
	{
		return $this->belongsTo(AcademicPeriod::class, 'academic_period_id');
	}

	public function recorder(): BelongsTo
	{
		return $this->belongsTo(User::class, 'recorded_by');
	}

	public function justifiedBy(): BelongsTo
	{
		return $this->belongsTo(User::class, 'justified_by');
	}

	public function justifications(): HasMany
	{
		return $this->hasMany(AttendanceJustification::class);
	}
}
