<?php

namespace Database\Factories;

use App\Models\ExamResource;
use App\Models\ExamResourceAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExamResourceAttachment> */
class ExamResourceAttachmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'exam_resource_id' => ExamResource::factory(),
            'kind' => ExamResourceAttachment::KIND_DOCUMENT,
            'disk' => 'local',
            'path' => 'exam-bank/'.fake()->uuid().'.pdf',
            'original_name' => 'epreuve.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(10_000, 500_000),
            'watermarked' => false,
        ];
    }

    public function answerKey(): static
    {
        return $this->state(fn () => [
            'kind' => ExamResourceAttachment::KIND_ANSWER_KEY,
            'original_name' => 'corrige.pdf',
        ]);
    }
}
