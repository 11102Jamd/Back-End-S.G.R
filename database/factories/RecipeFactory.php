<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Recipe>
 */
class RecipeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'recipe_name' => $this->faker->words(3, true),
            'yield_quantity' => $this->faker->randomFloat(2, 1, 100),
            'unit' => $this->faker->randomElement(['kg', 'L']),
        ];
    }
}
