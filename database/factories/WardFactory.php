<?php

namespace Database\Factories;

use App\Models\Constituency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ward>
 */
class WardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'constituency_id' => Constituency::factory(),
            'name' => $this->faker->unique()->streetName().' Ward',
            'code' => $this->faker->optional()->bothify('W-###'),
        ];
    }
}
