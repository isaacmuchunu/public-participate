<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubmissionDraft>
 */
class SubmissionDraftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $submissionType = $this->faker->randomElement(['support', 'oppose', 'amend', 'neutral']);

        return [
            'user_id' => User::factory(),
            'bill_id' => Bill::factory(),
            'submission_type' => $submissionType,
            'language' => $this->faker->randomElement(['en', 'sw', 'other']),
            'content' => $this->faker->paragraphs(3, true),
            'contact_information' => [
                'name' => $this->faker->name(),
                'email' => $this->faker->safeEmail(),
                'phone' => $this->faker->phoneNumber(),
                'county' => $this->faker->city(),
            ],
            'attachments' => [
                [
                    'id' => $this->faker->uuid(),
                    'name' => $this->faker->words(3, true).'.pdf',
                    'size' => $this->faker->numberBetween(50_000, 250_000),
                    'mime_type' => 'application/pdf',
                ],
            ],
            'submitted_at' => null,
        ];
    }
}
