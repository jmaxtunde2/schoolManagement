<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicPeriod extends Model
{
	use BelongsToSchool;

	protected $fillable = [
		'academic_year_id',
		'name',
		'position',
		'starts_at',
		'ends_at',
		'is_closed',
	];

	protected function casts(): array
	{
		return [
			'starts_at' => 'date:Y-m-d',
			'ends_at' => 'date:Y-m-d',
			'is_closed' => 'boolean',
		];
	}

	public function academicYear(): BelongsTo
	{
		return $this->belongsTo(AcademicYear::class);
	}

	public function attendanceRecords(): HasMany
	{
		return $this->hasMany(AttendanceRecord::class);
	}
}
