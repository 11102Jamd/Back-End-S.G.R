<?php

namespace Database\Factories;

use App\Models\Input;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Recipe;

class ProductionFactory extends Factory
{
    protected $model = Input::class;

    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'production_date' => $this->faker->date(),
        ];
    }
}
