<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserSession>
 */
class UserSessionFactory extends Factory
{
    protected $model = UserSession::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'session_id' => (string) Str::uuid(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'device' => fake()->randomElement(['Windows', 'Mac', 'Android', 'iPhone']),
            'location' => fake()->city(),
            'login_at' => now()->subMinutes(10),
            'last_activity_at' => now(),
        ];
    }
}
