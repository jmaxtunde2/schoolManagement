<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimetableFactory extends Factory
{
    protected $model = Timetable::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'class_id' => ClassRoom::factory(),
            'subject_id' => Subject::factory(),
            'teacher_id' => null,
            'day_of_week' => fake()->numberBetween(1, 5),
            'starts_at' => sprintf('%02d:00', fake()->numberBetween(7, 15)),
            'ends_at' => sprintf('%02d:55', fake()->numberBetween(8, 16)),
            'room' => 'Salle '.fake()->numberBetween(1, 20),
        ];
    }

    public function forSchool(School $school): static
    {
        return $this->state(['school_id' => $school->id]);
    }

    public function onDay(int $day): static
    {
        return $this->state(['day_of_week' => $day]);
    }

    public function withTeacher(Teacher $teacher): static
    {
        return $this->state(['teacher_id' => $teacher->id]);
    }
}
