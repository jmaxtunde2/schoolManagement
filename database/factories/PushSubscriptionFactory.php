<?php

namespace Database\Factories;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PushSubscription> */
class PushSubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'endpoint' => 'https://push.example.com/'.Str::uuid(),
            'public_key' => Str::random(32),
            'auth_token' => Str::random(16),
            'content_encoding' => 'aes128gcm',
            'domain' => 'localhost',
            'user_agent' => 'Mozilla/5.0',
            'last_used_at' => null,
        ];
    }
}
