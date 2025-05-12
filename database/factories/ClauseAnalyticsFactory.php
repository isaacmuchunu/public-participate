<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClauseAnalytics>
 */
class ClauseAnalyticsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $supportCount = fake()->numberBetween(0, 100);
        $opposeCount = fake()->numberBetween(0, 100);
        $neutralCount = fake()->numberBetween(0, 50);

        return [
            'clause_id' => \App\Models\BillClause::factory(),
            'submissions_count' => $supportCount + $opposeCount + $neutralCount,
            'support_count' => $supportCount,
            'oppose_count' => $opposeCount,
            'neutral_count' => $neutralCount,
            'sentiment_scores' => [
                'positive' => fake()->randomFloat(2, 0, 1),
                'negative' => fake()->randomFloat(2, 0, 1),
                'neutral' => fake()->randomFloat(2, 0, 1),
            ],
            'top_keywords' => fake()->words(5),
            'last_analyzed_at' => fake()->optional()->dateTimeBetween('-7 days', 'now'),
        ];
    }

    /**
     * Create analytics with high support
     */
    public function highSupport(): static
    {
        return $this->state(fn (array $attributes) => [
            'support_count' => 80,
            'oppose_count' => 10,
            'neutral_count' => 10,
            'submissions_count' => 100,
        ]);
    }

    /**
     * Create analytics with high opposition
     */
    public function highOpposition(): static
    {
        return $this->state(fn (array $attributes) => [
            'support_count' => 10,
            'oppose_count' => 80,
            'neutral_count' => 10,
            'submissions_count' => 100,
        ]);
    }
}
