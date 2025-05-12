<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'reviewed', 'included', 'rejected']);
        $reviewedAt = $status === 'pending' ? null : $this->faker->dateTimeBetween('-2 weeks', 'now');

        return [
            'user_id' => User::factory(),
            'submitter_name' => $this->faker->name(),
            'submitter_phone' => $this->faker->optional()->phoneNumber(),
            'submitter_email' => $this->faker->optional()->safeEmail(),
            'submitter_county' => $this->faker->optional()->city(),
            'submission_type' => $this->faker->randomElement(['support', 'oppose', 'amend', 'neutral']),
            'content' => $this->faker->paragraphs(3, true),
            'channel' => 'web',
            'language' => $this->faker->randomElement(['en', 'sw', 'other']),
            'status' => $status,
            'metadata' => [
                'sentiment' => $this->faker->randomElement(['positive', 'neutral', 'negative']),
                'keywords' => $this->faker->words(3),
            ],
            'review_notes' => $reviewedAt ? $this->faker->sentence() : null,
            'reviewed_at' => $reviewedAt,
            'reviewed_by' => $reviewedAt ? User::factory() : null,
        ];
    }
}
