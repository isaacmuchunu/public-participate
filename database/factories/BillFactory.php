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
}
