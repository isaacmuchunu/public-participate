<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bill>
 */
class BillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement([
            'draft',
            'gazetted',
            'open_for_participation',
            'closed',
            'committee_review',
            'passed',
            'rejected',
        ]);

        $participationStart = $status === 'open_for_participation'
            ? fake()->dateTimeBetween('-7 days', 'now')
            : fake()->optional()->dateTimeBetween('-1 month', '+1 month');

        $participationEnd = $participationStart
            ? fake()->dateTimeBetween($participationStart, '+1 month')
            : null;

        return [
            'title' => fake()->sentence(6),
            'bill_number' => 'BILL-'.now()->year.'-'.Str::upper(Str::random(6)),
            'description' => fake()->paragraphs(3, true),
            'type' => fake()->randomElement(['public', 'private', 'money']),
            'house' => fake()->randomElement(['national_assembly', 'senate', 'both']),
            'status' => $status,
            'sponsor' => fake()->optional()->name(),
            'committee' => fake()->optional()->words(3, true),
            'gazette_date' => fake()->optional()->date(),
            'participation_start_date' => $participationStart ? $participationStart->format('Y-m-d') : null,
            'participation_end_date' => $participationEnd ? $participationEnd->format('Y-m-d') : null,
            'pdf_path' => null,
            'tags' => fake()->randomElements([
                'governance',
                'health',
                'education',
                'agriculture',
                'economy',
            ], fake()->numberBetween(1, 3)),
            'views_count' => fake()->numberBetween(0, 5000),
            'submissions_count' => fake()->numberBetween(0, 250),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Bill in draft status
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'participation_start_date' => null,
            'participation_end_date' => null,
        ]);
    }

    /**
     * Bill that is gazetted
     */
    public function gazetted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'gazetted',
            'gazette_date' => now()->subDays(5),
            'participation_start_date' => null,
            'participation_end_date' => null,
        ]);
    }

    /**
     * Bill that is open for participation
     */
    public function openForParticipation(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open_for_participation',
            'gazette_date' => now()->subDays(10),
            'participation_start_date' => now()->subDays(5),
            'participation_end_date' => now()->addDays(25),
        ]);
    }

    /**
     * Bill that is closed
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
            'gazette_date' => now()->subDays(60),
            'participation_start_date' => now()->subDays(50),
            'participation_end_date' => now()->subDays(20),
        ]);
    }

    /**
     * Bill in committee review
     */
    public function inCommitteeReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'committee_review',
            'gazette_date' => now()->subDays(60),
            'participation_start_date' => now()->subDays(50),
            'participation_end_date' => now()->subDays(20),
        ]);
    }

    /**
     * Bill that has passed
     */
    public function passed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'passed',
            'gazette_date' => now()->subDays(90),
            'participation_start_date' => now()->subDays(80),
            'participation_end_date' => now()->subDays(50),
        ]);
    }

    /**
     * Bill that was rejected
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'gazette_date' => now()->subDays(90),
            'participation_start_date' => now()->subDays(80),
            'participation_end_date' => now()->subDays(50),
        ]);
    }
}
