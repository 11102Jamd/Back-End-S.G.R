<?php

namespace Database\Factories;

use App\Models\Input;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecipeIngredient>
 */
class RecipeIngredientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'input_id' => Input::factory(),
            'quantity_required' => $this->faker->randomFloat(3, 0.1, 10),
        ];
    }
}
