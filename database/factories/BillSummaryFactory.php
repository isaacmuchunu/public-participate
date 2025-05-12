<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BillSummary>
 */
class BillSummaryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'simplified_summary_en' => $this->faker->paragraphs(2, true),
            'simplified_summary_sw' => $this->faker->optional()->paragraphs(2, true),
            'key_clauses' => [
                $this->faker->sentence(),
                $this->faker->sentence(),
                $this->faker->sentence(),
            ],
            'audio_path_en' => null,
            'audio_path_sw' => null,
            'generation_method' => $this->faker->randomElement(['ai', 'manual']),
            'generated_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
