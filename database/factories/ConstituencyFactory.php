<?php

namespace Database\Factories;

use App\Models\County;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Constituency>
 */
class ConstituencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'county_id' => County::factory(),
            'name' => $this->faker->unique()->city().' Constituency',
            'code' => $this->faker->optional()->bothify('C-###'),
        ];
    }
}
