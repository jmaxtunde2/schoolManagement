<?php

namespace Database\Factories;

use App\Enums\AnnouncementStatus;
use App\Models\Announcement;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Announcement> */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'created_by' => null,
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'channels' => [Announcement::CHANNEL_WHATSAPP, Announcement::CHANNEL_EMAIL],
            'audience_type' => Announcement::AUDIENCE_ALL,
            'levels' => null,
            'status' => AnnouncementStatus::Draft,
        ];
    }
}
