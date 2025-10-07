<?php

namespace Database\Factories;

use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ward = Ward::query()->inRandomOrder()->first() ?? Ward::factory()->create();
        $constituency = $ward->constituency;
        $county = $constituency?->county;

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'citizen',
            'legislative_house' => null,
            'phone' => fake()->unique()->numerify('07########'),
            'national_id' => fake()->unique()->numerify('2#########'),
            'county_id' => $county?->id,
            'constituency_id' => $constituency?->id,
            'ward_id' => $ward->id,
            'otp_verified_at' => now(),
            'phone_verified_at' => now(),
            'is_verified' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a citizen user
     */
    public function citizen(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'citizen',
            'legislative_house' => null,
        ]);
    }

    /**
     * Create a legislator user
     */
    public function legislator(?string $house = 'senate'): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'legislator',
            'legislative_house' => $house,
        ]);
    }

    /**
     * Create a clerk user
     */
    public function clerk(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'clerk',
            'legislative_house' => null,
        ]);
    }

    /**
     * Create an admin user
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'legislative_house' => null,
        ]);
    }
}
