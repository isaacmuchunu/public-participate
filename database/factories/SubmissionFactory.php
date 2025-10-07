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
            'bill_id' => \App\Models\Bill::factory(),
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

    /**
     * Submission in pending status
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'review_notes' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
        ]);
    }

    /**
     * Submission under review
     */
    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'under_review',
            'review_notes' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
        ]);
    }

    /**
     * Submission that is approved
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'review_notes' => $this->faker->sentence(),
            'reviewed_at' => now(),
            'reviewed_by' => User::factory(),
        ]);
    }

    /**
     * Submission that is rejected
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'review_notes' => $this->faker->sentence(),
            'reviewed_at' => now(),
            'reviewed_by' => User::factory(),
        ]);
    }

    /**
     * Submission that was included
     */
    public function included(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'included',
            'review_notes' => $this->faker->sentence(),
            'reviewed_at' => now(),
            'reviewed_by' => User::factory(),
        ]);
    }
}
