<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\CitizenEngagement;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CitizenEngagement>
 */
class CitizenEngagementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bill_id' => Bill::factory(),
            'submission_id' => Submission::factory(),
            'sender_id' => User::factory(),
            'recipient_id' => User::factory(),
            'subject' => $this->faker->sentence(6),
            'message' => $this->faker->paragraph(),
            'channel' => 'platform',
            'sent_at' => now(),
        ];
    }
}
