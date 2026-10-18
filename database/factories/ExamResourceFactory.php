<?php

namespace Database\Factories;

use App\Enums\ExamDifficulty;
use App\Enums\ExamResourceStatus;
use App\Enums\ExamResourceType;
use App\Models\ExamResource;
use App\Models\PlatformLevel;
use App\Models\PlatformSubject;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExamResource> */
class ExamResourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'school_id' => null,
            'platform_level_id' => PlatformLevel::factory(),
            'platform_subject_id' => PlatformSubject::factory(),
            'created_by' => null,
            'title' => 'Épreuve de '.fake()->unique()->word(),
            'description' => fake()->sentence(),
            'type' => ExamResourceType::Epreuve->value,
            'difficulty' => ExamDifficulty::Medium->value,
            'duration_minutes' => 60,
            'academic_year' => null,
            'period' => null,
            'content' => fake()->paragraph(),
            'is_free' => true,
            'status' => ExamResourceStatus::Draft->value,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => ExamResourceStatus::Published->value,
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => ExamResourceStatus::Archived->value]);
    }

    public function forSchool(School $school): static
    {
        return $this->state(fn () => ['school_id' => $school->id]);
    }
}
