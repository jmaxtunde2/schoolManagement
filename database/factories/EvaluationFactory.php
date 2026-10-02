<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class EvaluationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'class_id' => ClassRoom::factory(),
            'subject_id' => Subject::factory(),
            'teacher_id' => Teacher::factory(),
            'title' => 'Devoir '.fake()->numberBetween(1, 3),
            'type' => 'devoir',
            'evaluation_date' => now(),
            'max_score' => 20,
            'coefficient' => 1,
            'status' => 'draft',
        ];
    }

    public function validated(): static
    {
        return $this->state(['status' => 'validated', 'validated_at' => now()]);
    }
}
