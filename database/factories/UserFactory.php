<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    // role / is_active ne sont pas fillable : on les pose via forceFill après création.
    public function configure(): static
    {
        return $this->afterMaking(function (User $user) {
            $user->role ??= Role::Teacher;
            $user->is_active ??= true;
        });
    }

    public function admin(): static
    {
        return $this->afterMaking(fn (User $user) => $user->role = Role::Admin);
    }

    public function teacher(): static
    {
        return $this->afterMaking(fn (User $user) => $user->role = Role::Teacher);
    }

    public function inactive(): static
    {
        return $this->afterMaking(fn (User $user) => $user->is_active = false);
    }

    public function forSchool(School $school): static
    {
        return $this->state(['school_id' => $school->id]);
    }

    public function withoutSchool(): static
    {
        return $this->state(['school_id' => null]);
    }
}
