<?php

namespace Database\Factories;

use App\Models\Stone;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stone>
 */
class StoneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'seller_id' => User::factory()->seller(),
            'shape' => fake()->randomElement(Stone::SHAPES),
            'carat' => fake()->randomFloat(2, 0.3, 5),
            'color' => fake()->randomElement(Stone::COLORS),
            'clarity' => fake()->randomElement(Stone::CLARITIES),
            'cut' => fake()->randomElement(Stone::CUTS),
            'lab' => fake()->randomElement(Stone::LABS),
            'certificate_no' => (string) fake()->unique()->numerify('##########'),
            'price_per_carat' => fake()->numberBetween(1200, 14000),
            'status' => Stone::STATUS_AVAILABLE,
        ];
    }
}
