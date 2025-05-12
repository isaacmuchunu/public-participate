<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BillClause>
 */
class BillClauseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $clauseNumber = fake()->numberBetween(1, 50);
        $clauseType = fake()->randomElement(['section', 'subsection', 'paragraph', 'subparagraph']);

        return [
            'bill_id' => \App\Models\Bill::factory(),
            'clause_number' => (string) $clauseNumber,
            'clause_type' => $clauseType,
            'parent_clause_id' => null,
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(3, true),
            'metadata' => [
                'page_number' => fake()->numberBetween(1, 50),
                'line_start' => fake()->numberBetween(1, 100),
            ],
            'display_order' => $clauseNumber,
        ];
    }

    /**
     * Create a clause with a parent (nested clause)
     */
    public function withParent(\App\Models\BillClause $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'bill_id' => $parent->bill_id,
            'parent_clause_id' => $parent->id,
            'clause_number' => fake()->numberBetween(1, 20),
            'clause_type' => 'subsection',
        ]);
    }

    /**
     * Create a top-level section
     */
    public function section(): static
    {
        return $this->state(fn (array $attributes) => [
            'clause_type' => 'section',
            'parent_clause_id' => null,
        ]);
    }

    /**
     * Create a subsection
     */
    public function subsection(): static
    {
        return $this->state(fn (array $attributes) => [
            'clause_type' => 'subsection',
        ]);
    }
}
